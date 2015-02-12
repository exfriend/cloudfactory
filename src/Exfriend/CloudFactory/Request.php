<?php namespace Exfriend\CloudFactory;

use Exfriend\CloudFactory\Exception\ContentLengthUnknownException;
use Exfriend\CloudFactory\Exception\RequestFailedException;

class Request {

    public $id = false;
    public $url;


    public $request;
    public $response;

    public $error = false;
    public $info = array();

    public $engine;
    public $storage;
    public $ch;

    public $tries_current = 0;
    public $tries_max = 1;

    public $valid = false;

    private $decode_from = false;

    public $callbacks = array(
        'Load' => false,
        'Fail' => false,
        'Success' => false
    );


    // ------------------------------------------------------------------------
    // --[ Setters ]---------------------------------------------------------
    // ------------------------------------------------------------------------

    public function setOpt( $name, $value )
    {
        $this->request->options [ $name ] = $value;
        curl_setopt( $this->ch, $name, $value );
        return $this;
    }

    public function setId( $id )
    {
        $this->id = $id;
        return $this;
    }


    public function setCallback( $type, $value )
    {
        $this->callbacks[ $type ] = $value;
        return $this;
    }

    public function setStorage( $key, $value )
    {
        $this->storage->set( $key, $value );
        return $this;
    }

    public function decodeFrom( $encoding )
    {
        $this->decode_from = $encoding;
        return $this;
    }

    // -------------------------------------------------------------------
    // --[ some helpful aliases ]-----------------------------------------
    // -------------------------------------------------------------------

    public function maxTries( $tries_max )
    {
        $this->tries_max = $tries_max;
        return $this;
    }

    public function withConnectTimeout( $conn )
    {
        return $this->setOpt( CURLOPT_CONNECTTIMEOUT, $conn );
    }

    public function withLoadTimeout( $load )
    {
        return $this->setOpt( CURLOPT_TIMEOUT, $load );
    }

    public function withTimeouts( $load, $conn = null )
    {
        return $this->withLoadTimeout( $load )
            ->withConnectTimeout( $conn );
    }

    public function withCookies( $file )
    {

        if ( file_exists( $file ) )
        {
            return $this->setOpt( CURLOPT_COOKIEJAR, $file )
                ->setOpt( CURLOPT_COOKIEFILE, $file );
        }

        return $this->setOpt( CURLOPT_COOKIE, $file );

    }

    public function withSsl()
    {
        return $this->setOpt( CURLOPT_SSL_VERIFYHOST, 0 )
            ->setOpt( CURLOPT_SSL_VERIFYPEER, 0 );
    }


    public function sendHeaders( $headers = array() )
    {
        if ( !count( $headers ) )
            return $this;

        if ( !$this->isAssoc( $headers ) )
        {
            $headers = $this->request->mergeAssocHeaders( $headers );
        }

        return $this->setOpt( CURLOPT_HTTPHEADER, $headers );
    }

    public function withBasicAuth( $username, $password )
    {

        return $this->setOpt( CURLOPT_USERPWD, $username . ':' . $password );
    }

    public function withProxy( $ip, $type = CURLPROXY_HTTP )
    {
        return $this->setOpt( CURLOPT_PROXY, $ip )
            ->setOpt( CURLOPT_PROXYTYPE, $type );
    }

    /**
     * We need to override standard curl behavior for consistency
     * @param array $postdata
     * @return Request
     */
    public function post( array $postdata )
    {
        foreach ( $postdata as $k => $v )
        {
            $postdata[ $k ] = rawurlencode( $k ) . '=' . rawurlencode( $v );
        }

        $postdata_string = implode( '&', $postdata );

        return $this->setOpt( CURLOPT_POSTFIELDS, $postdata_string );
    }

    // ------------------------------------------------------------------------
    // --[ Callbacks ]---------------------------------------------------------
    // ------------------------------------------------------------------------

    public function onLoad( callable $callback )
    {
        $this->callbacks[ 'Load' ] = $callback;
        return $this;
    }

    public function onSuccess( callable $callback )
    {
        $this->callbacks[ 'Success' ] = $callback;
        return $this;
    }

    public function onFail( callable $callback )
    {
        $this->callbacks[ 'Fail' ] = $callback;
        return $this;
    }

    // ------------------------------------------------------------------------
    // --[ Other ]-------------------------------------------------------------
    // ------------------------------------------------------------------------

    private function isAssoc( $arr )
    {
        return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
    }


    public function fire_callback( $name )
    {
        if ( $this->callbacks[ $name ] )
        {
            return call_user_func( $this->callbacks[ $name ], $this );
        }

        switch ( $name )
        {
            case 'Load':
                $this->valid = true;
                break;
            case 'Success':
                break;
            case 'Fail':
                throw new RequestFailedException( 'Request id#' . $this->id . ' failed to load properly' );
                break;
        }

    }


    public function parseResultContent()
    {
        $this->info = curl_getinfo( $this->ch );
        $this->error = curl_error( $this->ch );
        $responseData = curl_multi_getcontent( $this->ch );
        $contentLength = null;
        if ( preg_match_all( '/.*Content-Length: (\d+).*/mi', $responseData, $matches ) )
        {
            $contentLength = array_pop( $matches[ 1 ] );
        }

        // HTTP/1.0 200 Connection established\r\nProxy-agent: Kerio WinRoute Firewall/6.2.2 build 1746\r\n\r\nHTTP
        if ( stripos( $responseData, "HTTP/1.0 200 Connection established\r\n\r\n" ) !== false )
        {
            $responseData = str_ireplace( "HTTP/1.0 200 Connection established\r\n\r\n", '', $responseData );
        }
        if ( is_null( $contentLength ) || $contentLength == 0 )
        {
            $this->response->headers = mb_substr( $responseData, 0, curl_getinfo( $this->ch, CURLINFO_HEADER_SIZE ) );
            $this->response->body = mb_substr( $responseData, curl_getinfo( $this->ch, CURLINFO_HEADER_SIZE ) );
        }
        else
        {
            $this->response->headers = mb_substr( $responseData, 0, mb_strlen( $responseData ) - $contentLength );
            $this->response->body = mb_substr( $responseData, mb_strlen( $responseData ) - $contentLength );
        }


        if ( $this->decode_from )
        {
            $this->response->body = mb_convert_encoding( $this->response->body, 'UTF-8', $this->decode_from );
        }

    }

    /*
     * If re-processing the request, we need to rebuild a cURL handle,
     * cause it deletes automatically by cURL.     *
     */
    public function rebuild_handle()
    {
        $this->ch = curl_init( $this->url );
        foreach ( $this->request->options as $key => $value )
        {
            curl_setopt( $this->ch, $key, $value );
        }
    }


    // ------------------------------------------------------------------------
    // --[ Construction ]------------------------------------------------------
    // ------------------------------------------------------------------------

    public static function factory( $url )
    {
        return new self( $url );
    }

    public function __construct( $url )
    {
        $this->request = new RequestInput();
        $this->response = new RequestOutput();
        $this->storage = new Storage();
        $this->url = $url;
        $this->ch = curl_init( $url );

        // setting default options
        foreach ( $this->request->options as $key => $value )
        {
            $this->setOpt( $key, $value );
        }

        return $this;
    }


}