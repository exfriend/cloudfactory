<?php

namespace Exfriend\CloudFactory;

class Callbacks
{
    protected $callbacks = [];

    public function add( $name, $callback )
    {
        if ( !isset( $this->callbacks[ $name ] ) )
        {
            $this->callbacks[ $name ] = [];
        }

        //        if ( !is_callable( $callback ) || !is_array( $callback ) || !method_exists( $callback[ 0 ], $callback[ 1 ] ) )
        //        {
        //            throw new Exception( 'Callback with name "' . $name . '" is not callable' );
        //        }
        $this->callbacks[ $name ][] = $callback;
    }

    public function __call( $method, $arguments = [] )
    {
        $this->call( $method, $arguments );
    }

    public function call( $name, Request $request )
    {
        if ( !isset( $this->callbacks[ $name ] ) )
        {
            switch ( $name )
            {
                case 'success':
                    break;
                case 'fail':
                    throw new RequestFailedException( 'Request id#' . $request->id() . ' failed to load properly once' );
                    break;
                case 'lastfail':
                    throw new RequestFailedException( 'Request id#' . $request->id() . ' totally failed to load properly' );
                    break;
            }
            return false;
        }

        foreach ( $this->callbacks[ $name ] as $callback )
        {
            call_user_func_array( $callback, [ $request ] );
        }
        return true;
    }

    public function __debugInfo()
    {
        return array_keys( $this->callbacks );
    }

}