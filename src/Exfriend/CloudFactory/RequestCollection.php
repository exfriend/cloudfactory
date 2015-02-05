<?php namespace Exfriend\CloudFactory;

class RequestCollection implements \Countable, \ArrayAccess, \IteratorAggregate {

    protected $items = array();

    function __construct()
    {
    }


    /**
     * @param $ch
     * @throws \Exception
     * @return Request
     */
    public function getByCh( $ch )
    {
        foreach ( $this->items as $k => $v )
        {
            if ( $v->ch == $ch )
            {
                return $v;
            }
        }

        throw new \Exception( 'Не получается найти ресурс в коллекции' );

    }

    public function getById( $id )
    {

        foreach ( $this->items as $k => $v )
        {
            if ( $v->id == $id )
            {
                return $v;
            }
        }

        throw new \Exception( 'Не получается найти ресурс в коллекции' );

    }

    public function setById( $request )
    {

        foreach ( $this->items as $k => $v )
        {
            if ( $v->id == $request->id )
            {
                $this->items[ $k ] = $request;
                return true;
            }
        }

        throw new \Exception( 'Не получается найти ресурс в коллекции' );

    }

    public function add( $request )
    {
        array_push( $this->items, $request );
    }

    public function clear()
    {
        $this->items = array();
    }


    public function first()
    {
        return reset( $this->items );
    }

    /*
     * Array on steroids
     */

    public function count()
    {
        return count( $this->items );
    }

    public function offsetExists( $offset )
    {
        return array_key_exists( $offset, $this->items );
    }

    public function offsetGet( $offset )
    {
        return $this->items[ $offset ];
    }

    public function offsetSet( $offset, $value )
    {
        $this->items[ $offset ] = $value;
    }

    public function offsetUnset( $offset )
    {
        unset( $this->items[ $offset ] );
    }

    public function toArray()
    {
        return $this->items;
    }

    public function getIterator()
    {
        return new \ArrayIterator( $this->items );
    }

}