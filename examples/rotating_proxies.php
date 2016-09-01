<?php

use Exfriend\CloudFactory\Engine;
use Exfriend\CloudFactory\Request;

require '../vendor/autoload.php';

//$url = 'http://proxy.zlad.tk/?protocol=socks5&format=text';
$url = 'http://simaland.rublemag.ru/proxy_list.txt';
$proxies = file($url);
$proxies = array_map('trim', $proxies);

$rotator = new Exfriend\Rotator\ProxyRotator($proxies);

$engine = (new Engine())
    ->setThreads(5)
    ->withUserAgent()
    ->withSsl()
    ->withTimeouts(10, 7);

for ($i = 0; $i < 10; $i++) {
    $currentProxy = $rotator->getWorkingProxy();
    $request = (new Request('http://httpbin.org/ip'))
        ->withProxy($currentProxy->getProxyString(), CURLPROXY_SOCKS5)
        ->store('proxy', $currentProxy)
        ->store('id', $i)
        ->maxTries(5)
        ->validateUsing(function (Request $request) {
            $valid = strpos($request->response, 'origin') !== false;
            return $valid;
        })
        ->onSuccess(function ($r) {
            $r->storage->get('proxy')->succeeded();
            //            echo "+";
            echo '[' . $r->storage->get('id') . '][Success]['.$r->tries_current.'/'.$r->tries_max.']' . PHP_EOL;
        })
        ->onFail(function (Request $r) use ($rotator) {
            //            echo "-";
            echo '[' . $r->storage->get('id') . '][Fail]['.$r->tries_current.'/'.$r->tries_max.'] ' . $r->error->getMessage() . PHP_EOL;
            $r->storage->get('proxy')->failed();
            $newProxy = $rotator->getWorkingProxy();
            $r->withProxy($newProxy->getProxyString(), CURLPROXY_SOCKS5);
            $r->store('proxy', $newProxy);
        })
        ->onLastFail(function ($r) {
            echo '[' . $r->storage->get('id') . '][LastFail] ' . $r->error->getMessage() . PHP_EOL;
        });
    $engine->addRequest($request);
}

$engine->run();
