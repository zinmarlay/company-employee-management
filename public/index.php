<?php

declare(strict_types=1);

use App\Bootstrap\ApplicationBootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $configuration = (new ApplicationBootstrap(dirname(__DIR__)))->boot();

    header('Content-Type: text/plain; charset=utf-8');

    if ($configuration->isDebug()) {
        echo $configuration->name() . "\n";
        echo "Phase 01 setup bootstrap is active.\n";
        echo 'Environment: ' . $configuration->environment() . "\n";
        echo 'Timezone: ' . $configuration->timezone() . "\n";
        echo "Routing and application features are intentionally deferred.\n";
    } else {
        echo 'Company Employee Management System is running.';
    }
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Application setup error.';
}
