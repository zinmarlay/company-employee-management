<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Employee\EmployeeService;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Application\Dispatch\DispatchCompanyService;
use App\Application\Dispatch\DispatchContractService;
use App\Application\Support\SystemClock;
use App\Application\Validation\DispatchCompanyInputValidator;
use App\Application\Validation\DispatchContractInputValidator;
use App\Application\Validation\EmployeeInputValidator;
use App\Database\LazyPdoConnection;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DispatchCompanyController;
use App\Http\Controllers\DispatchContractController;
use App\Http\Controllers\SetupController;
use App\Http\Middleware\LocaleMiddleware;
use App\Infrastructure\Persistence\PdoBranchReadRepository;
use App\Infrastructure\Persistence\PdoDepartmentReadRepository;
use App\Infrastructure\Persistence\PdoEmployeeRepository;
use App\Infrastructure\Persistence\PdoDispatchCompanyRepository;
use App\Infrastructure\Persistence\PdoDispatchContractRepository;
use App\Localization\Translator;
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

        $translator = new Translator($this->projectRoot . '/resources/lang');
        $viewRenderer = new ViewRenderer($this->projectRoot . '/resources/views', $translator);
        $setupController = new SetupController($viewRenderer, $configuration);
        $connection = new LazyPdoConnection($configuration);
        $dispatchCompanies = new PdoDispatchCompanyRepository($connection);
        $dispatchContracts = new PdoDispatchContractRepository($connection);
        $expiration = new ContractExpirationClassifier();
        $clock = new SystemClock();
        $employeeService = new EmployeeService(
            new PdoEmployeeRepository($connection),
            new PdoBranchReadRepository($connection),
            new PdoDepartmentReadRepository($connection),
            new EmployeeInputValidator(),
            $clock,
            $dispatchContracts,
            $expiration,
        );
        $employeeController = new EmployeeController($viewRenderer, $employeeService, $configuration);
        $dispatchCompanyService = new DispatchCompanyService(
            $dispatchCompanies,
            $dispatchContracts,
            new DispatchCompanyInputValidator(),
            $clock,
            $expiration,
        );
        $dispatchContractService = new DispatchContractService(
            $dispatchContracts,
            $dispatchCompanies,
            new PdoEmployeeRepository($connection),
            new DispatchContractInputValidator(),
            $clock,
            $expiration,
        );
        $dispatchCompanyController = new DispatchCompanyController($viewRenderer, $dispatchCompanyService);
        $dispatchContractController = new DispatchContractController($viewRenderer, $dispatchContractService);
        $router = new Router();

        $registerRoutes = require $this->projectRoot . '/routes/web.php';
        $registerRoutes($router, $setupController, $employeeController, $dispatchCompanyController, $dispatchContractController);

        return new HttpKernel(
            $router,
            [new LocaleMiddleware($translator)],
            new ExceptionResponder($configuration->isDebug()),
        );
    }
}
