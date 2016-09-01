<?php

use Exfriend\CloudFactory\Engine;
use Exfriend\CloudFactory\Request;

require '../vendor/autoload.php';

$engine = (new Engine())->setThreads(50)->withUserAgent();

for ($i = 1; $i <= 10; $i++) {
    $engine->addRequest((new Request('http://httpbin.org/delay/1?i=' . $i)));
}

$resps = $engine->run()->requests->pluck('response');

xd($resps);
