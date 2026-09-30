<?php

declare(strict_types=1);

use App\Bootstrap\ApplicationBootstrap;
use App\Bootstrap\EnvironmentLoader;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseEmitter;
use App\Logging\ErrorLogLogger;
use App\Logging\LogContext;

require dirname(__DIR__) . '/vendor/autoload.php';

$emitter = new ResponseEmitter();
$request = Request::fromGlobals();
$logger = new ErrorLogLogger();

try {
    $projectRoot = dirname(__DIR__);
    EnvironmentLoader::load($projectRoot);
    $kernel = (new ApplicationBootstrap($projectRoot))->boot();
    $response = $kernel->handle($request);
} catch (Throwable $exception) {
    $logger->error('application_setup_failure', LogContext::request($request, [
        'outcome' => 'setup_failure',
        'status' => 500,
        'exception_class' => $exception::class,
    ]));
    $response = Response::text('Application setup error.', 500);
}

$emitter->emit($response);
