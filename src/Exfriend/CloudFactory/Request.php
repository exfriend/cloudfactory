<?php namespace Exfriend\CloudFactory;


define( 'CF_CALLBACK_VALID', 1 );
define( 'CF_CALLBACK_REPARSING', 2 );


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
    public $id;
    // url - основная переменная
    public $url;
    // полный набор опций
    public $options = array();
    // результат запроса
    public $result;
    // сетевая информация о запросе
    public $info = array();
    // хранилище данных для сквозной передачи в коллбек
    public $storage = array();
    // хэндл курла
    public $ch = false;
    // кол-во попыток загрузки
    public $retries = 1;
    public $current_tries = 0;
    // пока false - будет перепарсиваться
    public $valid = false;

    public $remoteEncoding;

    // коллбэки для репарсинга
    public $callbacks = array(
        CF_CALLBACK_VALID => false,
        CF_CALLBACK_REPARSING => false
    );

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


    public function withCookies( $file )
    {
        return $this->setOpt( CURLOPT_COOKIEJAR, $file )
            ->setOpt( CURLOPT_COOKIEFILE, $file );
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