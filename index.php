<?php
require 'vendor/autoload.php';

use GuzzleHttp\Ring\Client\CurlMultiHandler;

$handler = new CurlMultiHandler();


$request = [
    'http_method' => 'GET',
    'future' => true,

    'client' => [

        'progress' => function ( $a, $b )
        {
            var_dump( $a, $b );
            echo '<hr>';
        },

        'curl' => [
            CURLOPT_FOLLOWLOCATION => true
        ]
    ],
    'headers' => [ 'host' => [ 'google.com' ] ]
];

// this call returns a future array immediately.
$response = $handler( $request );

// Ideally, you should use the promise API to not block.
$response
    ->then( function ( $response )
    {
        // Got the response at some point in the future

        //var_dump( stream_get_contents($response['body']) );

        //echo $response['status']; // 200
        // Don't break the chain
        return $response;
    } )->then( function ( $response )
    {
        // ...
    } );

// If you really need to block, then you can use the response as an
// associative array. This will block until it has completed.
echo $response[ 'status' ]; // 200