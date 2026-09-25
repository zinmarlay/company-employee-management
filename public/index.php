<?php

declare(strict_types=1);

use App\Bootstrap\ApplicationBootstrap;
use App\Bootstrap\EnvironmentLoader;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseEmitter;

require dirname(__DIR__) . '/vendor/autoload.php';

$emitter = new ResponseEmitter();

try {
    $projectRoot = dirname(__DIR__);
    EnvironmentLoader::load($projectRoot);
    $kernel = (new ApplicationBootstrap($projectRoot))->boot();
    $response = $kernel->handle(Request::fromGlobals());
} catch (Throwable) {
    $response = Response::text('Application setup error.', 500);
}

$emitter->emit($response);
