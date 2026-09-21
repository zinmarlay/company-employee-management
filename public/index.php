<?php

declare(strict_types=1);

use App\Bootstrap\ApplicationBootstrap;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseEmitter;

require dirname(__DIR__) . '/vendor/autoload.php';

$emitter = new ResponseEmitter();

try {
    $kernel = (new ApplicationBootstrap(dirname(__DIR__)))->boot();
    $response = $kernel->handle(Request::fromGlobals());
} catch (Throwable) {
    $response = Response::text('Application setup error.', 500);
}

$emitter->emit($response);
