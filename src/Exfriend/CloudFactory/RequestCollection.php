<?php namespace Exfriend\CloudFactory;

use Illuminate\Support\Contracts\ArrayableInterface;


class RequestCollection implements \Countable, \ArrayAccess, ArrayableInterface, \IteratorAggregate
{

    protected $items = array();

    function __construct()
    {
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