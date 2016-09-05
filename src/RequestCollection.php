<?php namespace Exfriend\CloudFactory;

use Illuminate\Support\Collection;

class RequestCollection extends Collection
{

    protected $items = [];

    public function clear()
    {
        $this->items = [];
        return $this;
    }

    public function deleteById( $id )
    {
        $this->items = $this->keyBy( 'id' );
        $this->items->forget( $id );
        $this->items = $this->items->toArray();
        return $this;
    }

    public function unprocessed()
    {
        return $this->where( 'processed', false )->where( 'processing', false );
    }


    public function failedNotTooManyTimes()
    {
        return $this->filter( function ( Request $request )
        {
            return !$request->processing
            && (
                !$request->processed
                || ( $request->tries_current < $request->tries_max )
            );
        } );
    }

    public function processed()
    {
        return $this->where( 'processed', true );
    }


}
