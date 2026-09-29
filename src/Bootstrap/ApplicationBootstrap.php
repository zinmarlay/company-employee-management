<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Employee\EmployeeService;
use App\Application\Employee\EmployeeSkillService;
use App\Application\Employee\EmployeeProjectService;
use App\Application\Employee\EmployeeCertificationService;
use App\Application\Employee\EmployeePortfolioSummaryService;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Application\Dispatch\DispatchCompanyService;
use App\Application\Dispatch\DispatchContractService;
use App\Application\Organization\BranchService;
use App\Application\Organization\DepartmentService;
use App\Application\Organization\OrganizationDisplayNameResolver;
use App\Application\Support\SystemClock;
use App\Application\Validation\DispatchCompanyInputValidator;
use App\Application\Validation\DispatchContractInputValidator;
use App\Application\Validation\BranchInputValidator;
use App\Application\Validation\DepartmentInputValidator;
use App\Application\Validation\EmployeeInputValidator;
use App\Application\Validation\EmployeeSkillInputValidator;
use App\Application\Validation\EmployeeProjectInputValidator;
use App\Application\Validation\EmployeeCertificationInputValidator;
use App\Domain\Organization\PrefectureCatalog;
use App\Domain\Organization\DepartmentCatalog;
use App\Database\LazyPdoConnection;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSkillController;
use App\Http\Controllers\EmployeeProjectController;
use App\Http\Controllers\EmployeeCertificationController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispatchCompanyController;
use App\Http\Controllers\DispatchContractController;
use App\Http\Controllers\SetupController;
use App\Http\Middleware\LocaleMiddleware;
use App\Infrastructure\Persistence\PdoBranchReadRepository;
use App\Infrastructure\Persistence\PdoBranchRepository;
use App\Infrastructure\Persistence\PdoDepartmentReadRepository;
use App\Infrastructure\Persistence\PdoDepartmentRepository;
use App\Infrastructure\Persistence\PdoEmployeeRepository;
use App\Infrastructure\Persistence\PdoSkillRepository;
use App\Infrastructure\Persistence\PdoEmployeeProjectRepository;
use App\Infrastructure\Persistence\PdoEmployeeCertificationRepository;
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
        $branches = new PdoBranchRepository($connection);
        $departments = new PdoDepartmentRepository($connection);
        $expiration = new ContractExpirationClassifier();
        $clock = new SystemClock();
        $displayNames = new OrganizationDisplayNameResolver(new PrefectureCatalog(), new DepartmentCatalog());
        $employeeRepository = new PdoEmployeeRepository($connection);
        $skillRepository = new PdoSkillRepository($connection);
        $projectRepository = new PdoEmployeeProjectRepository($connection);
        $certificationRepository = new PdoEmployeeCertificationRepository($connection);
        $employeeService = new EmployeeService(
            $employeeRepository,
            new PdoBranchReadRepository($connection),
            new PdoDepartmentReadRepository($connection),
            new EmployeeInputValidator(),
            $clock,
            $dispatchContracts,
            $expiration,
            null,
            new PrefectureCatalog(),
            new DepartmentCatalog(),
            $translator,
            $displayNames,
        );
        $skillService = new EmployeeSkillService($skillRepository, $employeeRepository, new EmployeeSkillInputValidator(), $clock);
        $projectService = new EmployeeProjectService($projectRepository, $employeeRepository, new EmployeeProjectInputValidator(), $clock);
        $certificationService = new EmployeeCertificationService($certificationRepository, $employeeRepository, new EmployeeCertificationInputValidator(), $clock);
        $portfolioSummary = new EmployeePortfolioSummaryService($employeeRepository, $skillRepository, $projectRepository, $certificationRepository);
        $employeeController = new EmployeeController($viewRenderer, $employeeService, $configuration, $portfolioSummary);
        $skillController = new EmployeeSkillController($viewRenderer, $skillService);
        $projectController = new EmployeeProjectController($viewRenderer, $projectService);
        $certificationController = new EmployeeCertificationController($viewRenderer, $certificationService);
        $branchService = new BranchService($branches, $departments, new BranchInputValidator(), $clock, new PrefectureCatalog(), $displayNames, $translator);
        $departmentService = new DepartmentService($departments, $branches, new DepartmentInputValidator(), $clock, new DepartmentCatalog(), $displayNames, $translator);
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
            $employeeRepository,
            new DispatchContractInputValidator(),
            $clock,
            $expiration,
        );
        $dispatchCompanyController = new DispatchCompanyController($viewRenderer, $dispatchCompanyService);
        $dispatchContractController = new DispatchContractController($viewRenderer, $dispatchContractService);
        $branchController = new BranchController($viewRenderer, $branchService);
        $departmentController = new DepartmentController($viewRenderer, $departmentService);
        $router = new Router();

        $registerRoutes = require $this->projectRoot . '/routes/web.php';
        $registerRoutes(
            $router,
            $setupController,
            $employeeController,
            $branchController,
            $departmentController,
            $dispatchCompanyController,
            $dispatchContractController,
            $skillController,
            $projectController,
            $certificationController,
        );

        return new HttpKernel(
            $router,
            [new LocaleMiddleware($translator)],
            new ExceptionResponder($configuration->isDebug()),
        );
    }
}
