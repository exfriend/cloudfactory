<?php namespace Exfriend\CloudFactory;

use Symfony\Component\Stopwatch\Stopwatch;

class Statistics {

    private $request_info = array();
    protected $fields = array(
        'requests_loaded' => 0,
        'requests_total' => 0,
        'requests_valid' => 0,
        'requests_failed' => 0,
        'time_elapsed' => 0,
        'time_eta' => 0,
        'time_total_eta' => 0,
        'time_per_request_avg' => 0,
        'bytes_per_request_avg' => 0,
        'bytes_received' => 0,
        'bytes_total_eta' => 0,
        'bytes_eta' => 0,
        'percent' => 0,
        'speed_bytes_per_second' => 0,
        'speed_requests_per_second' => 0,
        'tries_avg' => 0,
        'tries_valid_avg' => 8,
    );
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    private function recount()
    {

        if ( $this->fields[ 'requests_total' ] > 0 )
        {
            $this->fields[ 'percent' ] = ceil( 100 * ( $this->fields[ 'requests_loaded' ] / $this->fields[ 'requests_total' ] ) );
        }

        try
        {
            $this->fields[ 'time_elapsed' ] = $this->stopwatch->getEvent( 'cloudfactory.run' )->getDuration();
        }
        catch ( \Exception $e )
        {
            $this->fields[ 'time_elapsed' ] = 0.00001;
        }
        $this->fields[ 'tries_avg' ] = 0;
        $this->fields[ 'tries_valid_avg' ] = 0;
        $this->fields[ 'bytes_received' ] = 0;

        foreach ( $this->request_info as $k => $v )
        {
            $this->fields[ 'bytes_received' ] += $v[ 'bytes_received' ];
            $this->fields[ 'tries_avg' ] += $v[ 'tries' ];
            $this->fields[ 'tries_valid_avg' ] += $v[ 'tries' ];
        }

        if ( $this->fields[ 'requests_loaded' ] > 0 )
        {
            $this->fields[ 'time_per_request_avg' ] = round( $this->fields[ 'time_elapsed' ] / $this->fields[ 'requests_loaded' ], 3 );
            $this->fields[ 'bytes_per_request_avg' ] = ceil( $this->fields[ 'bytes_received' ] / $this->fields[ 'requests_loaded' ] );
            $this->fields[ 'tries_avg' ] = round( ( $this->fields[ 'tries_avg' ] / $this->fields[ 'requests_loaded' ] ), 2 );
        }
        if ( $this->fields[ 'requests_valid' ] > 0 )
        {
            $this->fields[ 'tries_valid_avg' ] = round( ( $this->fields[ 'tries_valid_avg' ] / $this->fields[ 'requests_valid' ] ), 2 );
        }
        if ( $this->fields[ 'requests_total' ] > 0 )
        {
            $this->fields[ 'time_total_eta' ] = ( $this->fields[ 'time_per_request_avg' ] * $this->fields[ 'requests_total' ] );
            $this->fields[ 'bytes_total_eta' ] = ( $this->fields[ 'bytes_per_request_avg' ] * $this->fields[ 'requests_total' ] );
        }

        $this->fields[ 'speed_requests_per_second' ] = round( $this->fields[ 'requests_loaded' ] / $this->fields[ 'time_elapsed' ], 3 );
        $this->fields[ 'speed_bytes_per_second' ] = ceil( $this->fields[ 'bytes_received' ] / $this->fields[ 'time_elapsed' ] );

        $this->fields[ 'time_eta' ] = $this->fields[ 'time_total_eta' ] - $this->fields[ 'time_elapsed' ];
        $this->fields[ 'bytes_eta' ] = $this->fields[ 'bytes_total_eta' ] - $this->fields[ 'bytes_received' ];

        if ( $this->fields[ 'requests_loaded' ] == $this->fields[ 'requests_total' ] )
        {
            $this->fields[ 'time_eta' ] = 0;
            $this->fields[ 'bytes_eta' ] = 0;
        }

    }


    public function get()
    {
        $this->recount();
        return $this->fields;
    }

    public function hook_request_add()
    {
        $this->fields[ 'requests_total' ]++;
    }


    public function hook_request_Success( Request $request )
    {
        $this->fields[ 'requests_valid' ]++;
        $this->fields[ 'requests_loaded' ]++;
        $this->request_info [ ] = array(
            'time_elapsed' => $this->stopwatch->getEvent( 'cloudfactory.request.' . $request->id )->getDuration(),
            'bytes_received' => $request->info[ 'size_download' ],
            'speed_bytes_per_second' => $request->info[ 'speed_download' ],
            'tries' => $request->tries_current
        );
    }

    public function hook_request_Fail( Request $request )
    {
        $this->fields[ 'requests_failed' ]++;
        $this->fields[ 'requests_loaded' ]++;
        $this->request_info [ ] = array(
            'time_elapsed' => $this->stopwatch->getEvent( 'cloudfactory.request.' . $request->id )->getDuration(),
            'bytes_received' => $request->info[ 'size_download' ],
            'speed_bytes_per_second' => $request->info[ 'speed_download' ],
            'tries' => $request->tries_current
        );
    }


    function __construct( Stopwatch $stopwatch )
    {
        $this->stopwatch = $stopwatch;
    }


} 