<?php

namespace Exfriend\CloudFactory;

class Engine
{

    public $requests;
    public $options = array(
        CURLOPT_FOLLOWLOCATION => 1
    );


    public function run()
    {
        return $this;
    }

    public function setOpt( $name, $value )
    {
        $this->options [ $name ] = $value;
        return $this;
    }

    function __construct()
    {
        $this->requests = new RequestCollection();
    }

    public static function factory()
    {
        return new self();
    }

    public function addRequest( Request $request )
    {
        $request = $this->mergeOptionsForRequest( $request );

        $this->requests->add( $request );
        return $this;
    }


    protected function mergeOptionsForRequest( Request $request )
    {
        $opts = array();

        foreach ( $this->options as $k => $v )
        {
            $opts [ $k ] = $v;
            curl_setopt( $request->ch, $k, $v );
        }
        foreach ( $request->options as $k => $v )
        {
            $opts [ $k ] = $v;
            curl_setopt( $request->ch, $k, $v );
        }

        $request->options = $opts;

        return $request;
    }

}


?>
