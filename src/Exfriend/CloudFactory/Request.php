<?php namespace Exfriend\CloudFactory;

class Request
{
    // needs:
    // $this->remoteEncoding
    // $this->result
    use DecodableTrait;

    public static $default_options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    );

    // id для поиска при обработке многопотока
    public $id = false;
    // url - основная переменная
    public $url;
    // полный набор опций
    public $options = array();
    // результат запроса
    public $result;
    public $headers;
    // сетевая информация о запросе

    public $error = false;

    public $info = array();
    // хранилище данных для сквозной передачи в коллбек
    public $storage = array();
    // хэндл курла
    public $ch = false;
    // кол-во попыток загрузки

    public $tries_current = 0;
    public $tries_max = 1;

    public $timeout_conn = null;
    public $timeout_load = null;

    // пока false - будет перепарсиваться
    public $valid = false;

    public $engine = false;

    public $remoteEncoding;

    // коллбэки для репарсинга
    public $callbacks = array(
        'Load' => false,
        'Fail' => false,
        'Success' => false
    );
    protected $defaultCharset = 'utf-8';

    public function parseResultContent()
    {
        $curlHandle = $this->ch;
        $this->info = curl_getinfo( $curlHandle );
        $this->error = curl_error( $curlHandle );
        $responseData = curl_multi_getcontent( $curlHandle );
        preg_match_all( '/.*Content-Length: (\d+).*/mi', $responseData, $matches );
        $contentLength = array_pop( $matches[ 1 ] );
        // HTTP/1.0 200 Connection established\r\nProxy-agent: Kerio WinRoute Firewall/6.2.2 build 1746\r\n\r\nHTTP
        if ( stripos( $responseData, "HTTP/1.0 200 Connection established\r\n\r\n" ) !== false )
        {
            $responseData = str_ireplace( "HTTP/1.0 200 Connection established\r\n\r\n", '', $responseData );
        }
        if ( is_null( $contentLength ) || $contentLength == 0 )
        {
            $this->headers = mb_substr( $responseData, 0, curl_getinfo( $curlHandle, CURLINFO_HEADER_SIZE ) );
            $this->result = mb_substr( $responseData, curl_getinfo( $curlHandle, CURLINFO_HEADER_SIZE ) );
        }
        else
        {
            $this->headers = mb_substr( $responseData, 0, mb_strlen( $responseData ) - $contentLength );
            $this->result = mb_substr( $responseData, mb_strlen( $responseData ) - $contentLength );
        }

        if ( !$this->remoteEncoding )
        {

            $this->remoteEncoding = $this->detectClientCharset( $this->getResponseHeaders() );
        }
        if ( $this->remoteEncoding && $this->remoteEncoding != $this->defaultCharset )
        {
            $this->result = mb_convert_encoding( $this->result, $this->defaultCharset, $this->remoteEncoding );
        }
    }

    protected function detectClientCharset()
    {
        if ( isset( $this->info[ 'content_type' ] ) && preg_match( '/charset\s*=\s*([\w\-\d]+)/i', $this->info[ 'content_type' ], $m ) )
        {
            return strtolower( $m[ 1 ] );
        }
        return $this->defaultCharset;
    }

    public function getResponseHeaders( $assoc = false )
    {
        return $this->parseHeaders( $this->headers, $assoc );
    }


    protected function parseHeaders( $headersString, $associative = false )
    {
        $headers = array();
        preg_match_all( '/\n\s*((.*?)\s*\:\s*(.*?))[\r\n]/', $headersString, $m );
        foreach ( $m[ 1 ] as $i => $header )
        {
            if ( $associative )
            {
                $headers[ $m[ 2 ][ $i ] ] = $m[ 3 ][ $i ];
            }
            else
            {
                $headers[ ] = $header;
            }
        }
        return $headers;
    }

    public function fire_callback( $name )
    {
        if ( is_callable( $this->callbacks[ $name ] ) )
        {
            return call_user_func( $this->callbacks[ $name ], $this );
        }

        throw new \InvalidArgumentException( 'Callback ' . $name . ' is not callable' );
    }

    public function setId( $id )
    {
        $this->id = $id;
        return $this;
    }

    public function bindEngine( &$engine )
    {
        $this->engine = $engine;
    }

    public function maxTries( $tries_max )
    {
        $this->tries_max = $tries_max;
        return $this;
    }

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

    public function __construct( $url )
    {
        $this->url = $url;
        $this->ch = curl_init( $url );
        foreach ( self::$default_options as $key => $value )
        {
            $this->setOpt( $key, $value );
        }

        return $this;
    }

    public function setRemoteEncoding( $encoding )
    {
        $this->remoteEncoding = $encoding;
        return $this;
    }

    public function needs_reparsing()
    {
        return ( ( $this->current_tries < $this->retries ) && ( !$this->valid ) );
    }

    /*
     * При репарсинге надо пересоздавать cURL-хэндл,
     * т.к. после отработки запроса он удаляется.
     */
    public function rebuild_handle()
    {
        $this->ch = curl_init( $this->url );
        foreach ( $this->options as $key => $value )
        {
            curl_setopt( $this->ch, $key, $value );
        }
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
        return $this->setOpt( CURLOPT_COOKIEJAR, $file )
            ->setOpt( CURLOPT_COOKIEFILE, $file );
    }

    public function withSsl()
    {
        return $this->setOpt( CURLOPT_SSL_VERIFYHOST, 0 )
            ->setOpt( CURLOPT_SSL_VERIFYPEER, 0 );
    }

    // Переопределяем стандартное поведение курла
    public function post( array $postdata )
    {
        $postdata_string = '';

        foreach ( $postdata as $k => $v )
        {
            $postdata[ $k ] = rawurlencode( $k ) . '=' . rawurlencode( $v );
        }

        $postdata_string = implode( '&', $postdata );

        return $this->setOpt( CURLOPT_POSTFIELDS, $postdata_string );
    }

    public function withProxy( $ip, $type = CURLPROXY_HTTP )
    {
        return $this->setOpt( CURLOPT_PROXY, $ip )
            ->setOpt( CURLOPT_PROXYTYPE, $type );
    }

    public static function factory( $url )
    {
        return new self( $url );
    }

    public function setOpt( $name, $value )
    {
        $this->options [ $name ] = $value;
        curl_setopt( $this->ch, $name, $value );
        return $this;
    }

    public function setRetries( $v )
    {
        $this->retries = $v;
    }

    public function setCallback( $type, $value )
    {
        $this->callbacks[ $type ] = $value;
        return $this;
    }

    public function setStorage( $key, $value )
    {
        $this->storage[ $key ] = $value;
        return $this;
    }

    public function getResponse()
    {
        return $this->result;
    }

    public function getInfo( $key = false )
    {
        if ( $key && array_key_exists( $key, $this->info ) )
        {
            return $this->info[ $key ];
        }

        return $this->info;
    }


} 