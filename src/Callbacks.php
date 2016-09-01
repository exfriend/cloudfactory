<?php

namespace Exfriend\CloudFactory;

use Exception;

class Callbacks
{
    protected $callbacks = [];

    public function add($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback with name "' . $name . '" is not callable');
        }
        $this->callbacks[ $name ][] = $callback;
    }

    public function __call($method, $arguments = [])
    {
        $this->call($method, $arguments);
    }

    public function call($name, Request $request)
    {
        if (!isset($this->callbacks[ $name ])) {
            switch ($name) {
                case 'success':
                    break;
                case 'fail':
                    throw new RequestFailedException('Request id#' . $request->id() . ' failed to load properly once');
                    break;
                case 'lastfail':
                    throw new RequestFailedException('Request id#' . $request->id() . ' totally failed to load properly');
                    break;
            }
            return false;
        }

        foreach ($this->callbacks[ $name ] as $callback) {
            //            xd( $callback );
            call_user_func($callback, $request);
        }
        return true;
    }

    public function __debugInfo()
    {
        return array_keys($this->callbacks);
    }

}