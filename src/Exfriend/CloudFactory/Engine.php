<?php

namespace Exfriend\CloudFactory;

class Engine
{

    public $requests;
    public $threads;
    public $queue;
    public $metrics = false;
    public $statistics = false;

    public $options = array(
        CURLOPT_FOLLOWLOCATION => 1
    );

    /**
     * @return mixed
     */
    public function getThreads()
    {
        return $this->threads;
    }

    /**
     * @param mixed $threads
     */
    public function setThreads( $threads )
    {
        $this->threads = $threads;
        return $this;
    }

    public function addMetrics( $Metrics )
    {
        $this->metrics = $Metrics;
        return $this;
    }


    public function clear()
    {
        $this->requests->clear();

        if ( is_object( $this->metrics ) )
            $this->metrics->clear();

    }

    public function run()
    {
        if ( is_object( $this->metrics ) )
        {
            $this->metrics->start( 'cloudfactory.run' );
        }

        $this->queue = new Queue( $this->requests->toArray() );

        $this->threads = ( sizeof( $this->requests ) < $this->threads ) ? sizeof( $this->requests ) : $this->threads;

        $master = curl_multi_init();
        $curl_arr = array();


        for ( $i = 0; $i < $this->threads; $i++ )
        {
            $req = $this->queue->pop();
            curl_multi_add_handle( $master, $req->ch );
            $this->metrics->start( 'cloudfactory.request.' . $req->id );
        }

        do
        {
            while ( ( $execrun = curl_multi_exec( $master, $running ) ) == CURLM_CALL_MULTI_PERFORM ) ;
            if ( $execrun != CURLM_OK )
                break;
            // a request was just completed -- find out which one
            while ( $done = curl_multi_info_read( $master ) )
            {
                $this->metrics->resume( 'cloudfactory.processing' );
                $curr_req = $this->requests->getByCh( $done[ 'handle' ] );
                $curr_req->parseResultContent();

                $this->metrics->pause( 'cloudfactory.request.' . $curr_req->id );

                $curr_req->tries_current++;
                $this->requests->setById( $curr_req );

                $curr_req->fire_callback( 'Load' );
                if ( $curr_req->valid )
                {
                    $this->metrics->stop( 'cloudfactory.request.' . $curr_req->id );
                    $this->stats->hook_request_Success( $curr_req );
                    $curr_req->fire_callback( 'Success' );
                }
                else
                {
                    // Вызываем callback, который поменяет прокси or whatever
                    $curr_req->fire_callback( 'Fail' );

                    // добавляем запрос обратно в очередь
                    if ( $curr_req->tries_current < $curr_req->tries_max )
                    {
                        $this->metrics->resume( 'cloudfactory.request.' . $curr_req->id );
                        $curr_req->rebuild_handle();
                        $this->queue->push( $curr_req );
                    }
                    else
                    {
                        $this->stats->hook_request_Fail( $curr_req );
                    }
                }
                // сохраняем все что нашаманили
                $this->requests->setById( $curr_req );


                // start a new request (it's important to do this before removing the old one)
                if ( $n = $this->queue->pop() )
                {
                    curl_multi_add_handle( $master, $n->ch );
                    $this->metrics->start( 'cloudfactory.request.' . $n->id );
                }
                // remove the curl handle that just completed
                curl_multi_remove_handle( $master, $done[ 'handle' ] );
                $this->metrics->pause( 'cloudfactory.processing' );
            }
        } while ( $running );

        curl_multi_close( $master );

        if ( is_object( $this->metrics ) )
        {
            $this->metrics->stop( 'cloudfactory.run' );
        }


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
        $this->stats = new Statistics( $this );
    }

    public static function factory()
    {
        return new self();
    }

    public function addRequest( Request $request )
    {
        if ( $request->id === false )
        {
            $request->id = uniqid();
        }

        $request = $this->mergeOptionsForRequest( $request );
        $request->bindEngine( $this );
        $this->requests->add( $request );
        $this->stats->hook_request_add();

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
