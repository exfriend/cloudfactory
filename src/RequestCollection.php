<?php namespace Exfriend\CloudFactory;

use Illuminate\Support\Collection;

class RequestCollection extends Collection
{

    protected $items = [];

    public function clear()
    {
        $this->items = [];
    }

    public function unprocessed()
    {
        return $this->where('processed', false)->where('processing', false);
    }

    public function processed()
    {
        return $this->where('processed', true);
    }


}