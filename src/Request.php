<?php namespace Exfriend\CloudFactory;

class Request
{
    use SetRequestOptions, ValidatesItself;

    /**
     * @var string
     */
    public $url;
    /**
     * @var Options
     */
    public $options;
    /**
     * @var string
     */
    public $response = '';
    /**
     * @var string
     */
    public $error;
    /**
     * @var \Exfriend\CloudFactory\Storage
     */
    public $storage;
    /**
     * @var int
     */
    public $tries_current = 0;
    /**
     * @var int
     */
    public $tries_max = 1;
    /**
     * @var string|bool
     */
    public $remote_encoding = false;
    /**
     * @var bool
     */
    public $processed = false;
    /**
     * @var bool
     */
    public $processing = false;
    /**
     * @var Callbacks
     */
    protected $callbacks;
    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $priority = 0;

    /**
     * Request constructor.
     * @param $url
     */
    public function __construct( $url )
    {
        $this->url = $url;
        $this->callbacks = new Callbacks();
        $this->storage = new Storage();
        $this->options = new Options();

        $this->id = uniqid() . uniqid();

        // Workaround to make curl options work in guzzle.
        // This will not affect actual headers.
        $this->sendHeaders( [ '' ] );

        return $this;
    }

    /*
     * Helpers
     */

    public function id()
    {
        return $this->id;
    }

    public function store( $key, $value )
    {
        $this->storage->set( $key, $value );
        return $this;
    }

    public function decodeFrom( $encoding )
    {
        $this->remote_encoding = $encoding;
        return $this;
    }

    public function maxTries( $tries_max )
    {
        $this->tries_max = $tries_max;
        return $this;
    }

    public function withPriority( $priority )
    {
        $this->priority = $priority;
        return $this;
    }


    /*
     * Callbacks
     */

    public function fireCallback( $name )
    {
        $this->callbacks->call( $name, $this );
        return $this;
    }

    public function onSuccess( $callback )
    {
        return $this->addCallback( 'success', $callback );
    }

    public function addCallback( $name, $callback )
    {
        $this->callbacks->add( $name, $callback );
        return $this;
    }

    public function onFail( $callback )
    {
        return $this->addCallback( 'fail', $callback );
    }

    public function onLastFail( $callback )
    {
        return $this->addCallback( 'lastfail', $callback );
    }

    public function __toString()
    {
        return $this->response;
    }

}