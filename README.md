![logo](docs/v1.png)

CloudFactory is a nice and powerful library for building crawlers in PHP. It provides a simple, human readable API while keeping all
power of cURL under the hood. Key feature of CloudFactory is Request Validation. When using proxies in multi-threaded environment
you will often get lots of problems with timeouts, response spoofing, HTTP/proxy errors and invalid content.

The goal of CloudFactory is to minimize the effort needed for 100% valid content delivery, and you can achieve that by
using this library.

###Requirements

- PHP 7.0
- php-curl
- mbstring
- guzzle

### Installation

`composer require exfriend/cloudfactory=experimental`

## Basic Usage

```
<?php

require 'vendor/autoload.php';

$engine = new \Exfriend\CloudFactory\Engine();
$request = new \Exfriend\CloudFactory\Request('http://httpbin.org/get');
$engine->run($request);

print_r($request->response);
```

## Features

### Setting curl options
```
$request = (new \Exfriend\CloudFactory\Request('http://httpbin.org/get'))->setOpt(CURLOPT_FOLLOWLOCATION, true);
```


### Option setters
You can set raw curl options as well as use some helpers available on `Request` object.

```
$request = (new \Exfriend\CloudFactory\Request('http://httpbin.org/post'))
           ->setOpt(CURLOPT_FOLLOWLOCATION, true)
           ->withProxy('127.0.0.1:8080', CURLPROXY_SOCKS5)
           ->sendHeaders(['Foo' => 'Bar'])
           ->post(['user' => 'admin', 'pass' => 'secret'])
           ->withTimeouts(10, 5)
           ->withSsl();
```
You can set almost all of the options directly on the `Engine` instance:

```
$engine = new Engine()->withUserAgent()->withSsl()->withCookies('cookie.txt');
```


### Concurrent requests

```
$engine = (new \Exfriend\CloudFactory\Engine())->setThreads(25);

for ($i = 0; $i < 100; $i++) {
    $request = new \Exfriend\CloudFactory\Request('http://httpbin.org/get?i=' . $i);
    $engine->addRequest($request);
}

$engine->run();

foreach ($engine->requests->processed() as $request) {
    print_r($request->response);
}
```

### Request priority

You can set priority for each request by using `->withPriority(int)`.
This way when it comes to pulling the request from the queue, they will be sorted by priority.

The more priority you set, the higher your request will be in the queue.

### Request callbacks

```
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
```

### Response validation

You can validate your responses using `validateWith( callable )`
when building a request. Your callable should accept the instance of `Request` as a parameter
and return boolean true or false as a validity indicator.

Depending on the validity, engine will call callbacks provided with `onSuccess` or `onFail` or `onLastFail`.

You can set `maxTries( int )` to inform the engine how many times the request
should be repeated before it fails the last time.

Note that on the last failed try both onFail and onLastFail callback groups will be called.

### Passing additional data

You can use `store(key,value)` method when creating the request to
pass any additional data on the storage bag of your request.
You can access it from callback like this:
```
...
  ->store( 'user', $user )
  ->onSuccess(function($request)){
      $user = $request->storage->get('user');
  })
...
```

### Dynamically add requests
You can add new requests while engine is still running e.g. from callback.

```
   ->onSuccess(function($r)use($engine){
       $newRequest = new Request('http://site.com/simething_else')
                        ->withSsl()
                        ->onSuccess('some_other_callback');
       $engine->addRequest($newRequest);
   })
```

### Request collection

When you add a request with `$engine->addRequest()` it gets pushed to `$engine->requests` collection.
It extends `Illuminate\Support\Collection` which is the powerful
collection pattern implementation used in Laravel. This means you have all
the perks like `$htmls = $engine->requests()->processed()->pluck('response')` to get all responses as array.

Full documentation on collections can be found on the [Official Laravel Documentation](https://laravel.com/docs/5.2/collections) pages.

### Callback stacking

You can process the results using `$engine->requests` collection or/and using callbacks.

You can add multiple callbacks for each state.
```
   ...
     ->onSuccess('parse_content_and_save_it_to_db')
     ->onSuccess('notify_other_class')
     ->onFail('show_notification')
     ->onFail('change_proxy')
     ->onLastFail([$this,'send_some_email'])
   ...
```

This gives you a possibility to easily write for example proxy 
rotating trait for your own descendant or `Request` class,

## Examples

For more examples check out the `./examples` directory.


###Contributing

This package is work-in-progress.