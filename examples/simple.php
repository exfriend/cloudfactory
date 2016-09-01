<?php

require '../vendor/autoload.php';

$engine = new \Exfriend\CloudFactory\Engine();

$request = new \Exfriend\CloudFactory\Request('http://httpbin.org/get');

$engine->run($request);

print_r($request->response);