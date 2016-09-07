<?php

require '../vendor/autoload.php';

$engine = (new \Exfriend\CloudFactory\Engine())->setThreads(25);

for ($i = 0; $i < 100; $i++) {
    $request = new \Exfriend\CloudFactory\Request('http://httpbin.org/get?i=' . $i);
    $engine->addRequest($request);
}

$engine->run();

foreach ($engine->requests->processed() as $request) {
    print_r($request->response);
}
