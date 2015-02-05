<?php namespace Exfriend\CloudFactory;

class Queue {
    protected $requests = array();

    function __construct( $requests )
    {
        $this->requests = $requests;
    }

    public function getByCh( $ch )
    {
        foreach ( $this->requests as $k => $v )
        {
            if ( $v->ch == $ch )
            {
                return $v;
            }
        }

        throw new \RequestDoesNotExistException( 'Не получается найти ресурс в очереди' );

    }

    public function push( Request $request )
    {
        $this->requests[ ] = $request;
    }

    public function pop()
    {
        return array_shift( $this->requests );
    }

    public function count()
    {
        return count( $this->requests );
    }

    public function clear()
    {
        $this->requests = array();
    }
}