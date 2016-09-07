<?php

use Exfriend\CloudFactory\Engine;
use Exfriend\CloudFactory\Request;

require '../vendor/autoload.php';

function request_success(Request $request)
{
    echo '$i=' . $request->storage->get('i') . ' Success callback: ' . $request->url . PHP_EOL;
}

$engine = (new Engine())
    ->setThreads(25)
    ->withUserAgent()
    ->withSsl()
    ->withTimeouts(10, 5);

for ($i = 0; $i < 50; $i++) {
    $request = (new Request('http://httpbin.org/get'))
        ->maxTries(3)
        ->store('i', $i)// to pass through to the callback
        ->validateUsing(function (Request $request) { // using closure
            // must return boolean
            return strpos($request->response, 'origin') !== false;
        })
        ->onSuccess('request_success')// using string
        ->onSuccess(function ($r) {
            echo 'You can stack callbacks of one type' . PHP_EOL;
        })
        ->onFail(function ($r) {
            echo "Request failed {$r->tries_current} times of {$r->tries_max}: " . $r->url;
        })
        ->onLastFail(function ($r) {
            echo "Request failed last time: " . $r->url;
        });
    $engine->addRequest($request);
}

$engine->run();

foreach ($engine->requests->processed() as $request) {
    print_r($request->response);
}
