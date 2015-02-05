#What is CloudFactory?

CloudFactory is a nice and powerful library for building crawlers in PHP. It provides a simple, human readable API while keeping all the
power of cURL under the hood.

The key feature is "reparsing" - an algorithm for 100% content delivery, which is crucial if you are working with proxy.
For example, you need to load a bunch of URLs with a bunch of proxies in multi-thread mode, and half of your proxy list does not work.
So, you need to analyze responses, change proxies or whatever and re-send while all of the pages are loaded. That's what reparsing is for!
All you need is three callbacks for validating responses and changing parameters.

Also, since every request is an object, you can set every option(even a callback) individually for each request. That makes CloudFactory a
powerful, reliable and at the same time flexible tool. If you work with crawlers - give it a try.

#Requirements

- PHP 5.4+
- php-curl
- mbstring
- Composer

#Composer dependencies

- symfony/stopwatch


#Basic usage

```

<?php
require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload
use Exfriend\CloudFactory\Engine;
use Exfriend\CloudFactory\Request;


$cf = Engine::factory()->setThreads( 10 );


for ( $i = 0; $i < 10; $i++ )
{
    $request = Request::factory( 'https://github.com/?i=' . $i )
        ->setStorage( 'i', $i )
        ->setId( $i )
        ->setOpt( CURLOPT_HEADER, true )
        ->withSsl()
        ->withCookies( 'ololo.txt' )
        ->withConnectTimeout( 4 )
        ->withLoadTimeout( 5 )
        ->maxTries( 10 );

    $cf->addRequest( $request );
}

$cf->run();


```


#Examples

Coming soon.

#TODO

- Ability to auto adjust thread count/timeouts on the fly (for long processes)