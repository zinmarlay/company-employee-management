<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Employee\EmployeeService;
use App\Application\Support\SystemClock;
use App\Application\Validation\EmployeeInputValidator;
use App\Database\LazyPdoConnection;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SetupController;
use App\Infrastructure\Persistence\PdoBranchReadRepository;
use App\Infrastructure\Persistence\PdoDepartmentReadRepository;
use App\Infrastructure\Persistence\PdoEmployeeRepository;
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
        $connection = new LazyPdoConnection($configuration);
        $employeeService = new EmployeeService(
            new PdoEmployeeRepository($connection),
            new PdoBranchReadRepository($connection),
            new PdoDepartmentReadRepository($connection),
            new EmployeeInputValidator(),
            new SystemClock(),
        );
        $employeeController = new EmployeeController($viewRenderer, $employeeService, $configuration);
        $router = new Router();

        $registerRoutes = require $this->projectRoot . '/routes/web.php';
        $registerRoutes($router, $setupController, $employeeController);

        return new HttpKernel(
            $router,
            [],
            new ExceptionResponder($configuration->isDebug()),
        );
    }
}
