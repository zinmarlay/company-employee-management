<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Controllers\SetupController;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;

final class ApplicationBootstrap
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    public function boot(): HttpKernel
    {
        $configuration = Configuration::fromEnvironment($this->projectRoot . '/config/app.php');
        date_default_timezone_set($configuration->timezone());

        $viewRenderer = new ViewRenderer($this->projectRoot . '/resources/views');
        $setupController = new SetupController($viewRenderer, $configuration);
        $router = new Router();

        $registerRoutes = require $this->projectRoot . '/routes/web.php';
        $registerRoutes($router, $setupController);

        return new HttpKernel(
            $router,
            [],
            new ExceptionResponder($configuration->isDebug()),
        );
    }
}
