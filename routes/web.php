<?php

declare(strict_types=1);

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSkillController;
use App\Http\Controllers\EmployeeProjectController;
use App\Http\Controllers\EmployeeCertificationController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispatchCompanyController;
use App\Http\Controllers\DispatchContractController;
use App\Http\Controllers\SetupController;
use App\Http\Routing\Router;

return static function (
    Router $router,
    SetupController $setupController,
    EmployeeController $employeeController,
    BranchController $branchController,
    DepartmentController $departmentController,
    DispatchCompanyController $dispatchCompanyController,
    DispatchContractController $dispatchContractController,
    EmployeeSkillController $skillController,
    EmployeeProjectController $projectController,
    EmployeeCertificationController $certificationController,
): void {
    $router->get('/', $setupController);
    $router->get('/employees', [$employeeController, 'index']);
    $router->get('/employees/create', [$employeeController, 'create']);
    $router->post('/employees', [$employeeController, 'store']);
    $router->get('/employees/{id}/deactivate', [$employeeController, 'deactivateConfirmation']);
    $router->post('/employees/{id}/deactivate', [$employeeController, 'deactivate']);

    $router->get('/employees/{employeeId}/skills', [$skillController, 'index']);
    $router->get('/employees/{employeeId}/skills/create', [$skillController, 'create']);
    $router->post('/employees/{employeeId}/skills', [$skillController, 'store']);
    $router->get('/employees/{employeeId}/skills/{skillId}/edit', [$skillController, 'edit']);
    $router->post('/employees/{employeeId}/skills/{skillId}', [$skillController, 'update']);
    $router->get('/employees/{employeeId}/skills/{skillId}/archive', [$skillController, 'archiveConfirmation']);
    $router->post('/employees/{employeeId}/skills/{skillId}/archive', [$skillController, 'archive']);

    $router->get('/employees/{employeeId}/projects', [$projectController, 'index']);
    $router->get('/employees/{employeeId}/projects/create', [$projectController, 'create']);
    $router->post('/employees/{employeeId}/projects', [$projectController, 'store']);
    $router->get('/employees/{employeeId}/projects/{projectId}', [$projectController, 'show']);
    $router->get('/employees/{employeeId}/projects/{projectId}/edit', [$projectController, 'edit']);
    $router->post('/employees/{employeeId}/projects/{projectId}', [$projectController, 'update']);
    $router->get('/employees/{employeeId}/projects/{projectId}/archive', [$projectController, 'archiveConfirmation']);
    $router->post('/employees/{employeeId}/projects/{projectId}/archive', [$projectController, 'archive']);

    $router->get('/employees/{employeeId}/certifications', [$certificationController, 'index']);
    $router->get('/employees/{employeeId}/certifications/create', [$certificationController, 'create']);
    $router->post('/employees/{employeeId}/certifications', [$certificationController, 'store']);
    $router->get('/employees/{employeeId}/certifications/{certificationId}', [$certificationController, 'show']);
    $router->get('/employees/{employeeId}/certifications/{certificationId}/edit', [$certificationController, 'edit']);
    $router->post('/employees/{employeeId}/certifications/{certificationId}', [$certificationController, 'update']);
    $router->get('/employees/{employeeId}/certifications/{certificationId}/archive', [$certificationController, 'archiveConfirmation']);
    $router->post('/employees/{employeeId}/certifications/{certificationId}/archive', [$certificationController, 'archive']);
    $router->get('/employees/{id}/edit', [$employeeController, 'edit']);
    $router->post('/employees/{id}', [$employeeController, 'update']);
    $router->get('/employees/{id}', [$employeeController, 'show']);

    $router->get('/branches', [$branchController, 'index']);
    $router->get('/branches/create', [$branchController, 'create']);
    $router->post('/branches', [$branchController, 'store']);
    $router->get('/branches/{id}/deactivate', [$branchController, 'deactivateConfirmation']);
    $router->post('/branches/{id}/deactivate', [$branchController, 'deactivate']);
    $router->get('/branches/{id}/edit', [$branchController, 'edit']);
    $router->post('/branches/{id}', [$branchController, 'update']);
    $router->get('/branches/{id}', [$branchController, 'show']);

    $router->get('/departments', [$departmentController, 'index']);
    $router->get('/departments/create', [$departmentController, 'create']);
    $router->post('/departments', [$departmentController, 'store']);
    $router->get('/departments/{id}/deactivate', [$departmentController, 'deactivateConfirmation']);
    $router->post('/departments/{id}/deactivate', [$departmentController, 'deactivate']);
    $router->get('/departments/{id}/edit', [$departmentController, 'edit']);
    $router->post('/departments/{id}', [$departmentController, 'update']);
    $router->get('/departments/{id}', [$departmentController, 'show']);

    $router->get('/dispatch-companies', [$dispatchCompanyController, 'index']);
    $router->get('/dispatch-companies/create', [$dispatchCompanyController, 'create']);
    $router->post('/dispatch-companies', [$dispatchCompanyController, 'store']);
    $router->get('/dispatch-companies/{id}/deactivate', [$dispatchCompanyController, 'deactivateConfirmation']);
    $router->post('/dispatch-companies/{id}/deactivate', [$dispatchCompanyController, 'deactivate']);
    $router->get('/dispatch-companies/{id}/edit', [$dispatchCompanyController, 'edit']);
    $router->post('/dispatch-companies/{id}', [$dispatchCompanyController, 'update']);
    $router->get('/dispatch-companies/{id}', [$dispatchCompanyController, 'show']);

    $router->get('/dispatch-contracts/create', [$dispatchContractController, 'create']);
    $router->post('/dispatch-contracts', [$dispatchContractController, 'store']);
    $router->get('/dispatch-contracts/{id}/renew', [$dispatchContractController, 'renew']);
    $router->post('/dispatch-contracts/{id}/renew', [$dispatchContractController, 'renewStore']);
    $router->get('/dispatch-contracts/{id}/edit', [$dispatchContractController, 'edit']);
    $router->post('/dispatch-contracts/{id}', [$dispatchContractController, 'update']);
    $router->get('/dispatch-contracts/{id}', [$dispatchContractController, 'show']);
};
