<?php

require '../vendor/autoload.php';

$engine = (new \Exfriend\CloudFactory\Engine())
    ->setThreads(25)
    ->withUserAgent()
    ->withSsl();

for ($i = 0; $i < 50; $i++) {
    $request = (new \Exfriend\CloudFactory\Request('http://httpbin.org/post?i=' . $i))
        ->post(['a' => 'b'])
        ->store('iter', $i) // pass some data
        ->withTimeouts(10, 5);
    $engine->addRequest($request);
}

$engine->run();

foreach ($engine->requests->processed() as $request) {
    echo $request->storage->get('iter');
    print_r($request->response);
}
