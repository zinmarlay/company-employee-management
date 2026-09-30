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
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SystemUserController;
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
    LoginController $loginController,
    SystemUserController $systemUserController,
): void {
    $router->get('/login', [$loginController, 'show'], 'public');
    $router->post('/login', [$loginController, 'authenticate'], 'public');
    $router->post('/logout', [$loginController, 'logout'], 'authenticated');

    $router->get('/', $setupController, 'authenticated_read');
    $router->get('/employees', [$employeeController, 'index'], 'authenticated_read');
    $router->get('/employees/create', [$employeeController, 'create'], 'admin');
    $router->post('/employees', [$employeeController, 'store'], 'admin');
    $router->get('/employees/{id}/deactivate', [$employeeController, 'deactivateConfirmation'], 'admin');
    $router->post('/employees/{id}/deactivate', [$employeeController, 'deactivate'], 'admin');

    $router->get('/employees/{employeeId}/skills', [$skillController, 'index'], 'authenticated_read');
    $router->get('/employees/{employeeId}/skills/create', [$skillController, 'create'], 'admin');
    $router->post('/employees/{employeeId}/skills', [$skillController, 'store'], 'admin');
    $router->get('/employees/{employeeId}/skills/{skillId}/edit', [$skillController, 'edit'], 'admin');
    $router->post('/employees/{employeeId}/skills/{skillId}', [$skillController, 'update'], 'admin');
    $router->get('/employees/{employeeId}/skills/{skillId}/archive', [$skillController, 'archiveConfirmation'], 'admin');
    $router->post('/employees/{employeeId}/skills/{skillId}/archive', [$skillController, 'archive'], 'admin');

    $router->get('/employees/{employeeId}/projects', [$projectController, 'index'], 'authenticated_read');
    $router->get('/employees/{employeeId}/projects/create', [$projectController, 'create'], 'admin');
    $router->post('/employees/{employeeId}/projects', [$projectController, 'store'], 'admin');
    $router->get('/employees/{employeeId}/projects/{projectId}', [$projectController, 'show'], 'authenticated_read');
    $router->get('/employees/{employeeId}/projects/{projectId}/edit', [$projectController, 'edit'], 'admin');
    $router->post('/employees/{employeeId}/projects/{projectId}', [$projectController, 'update'], 'admin');
    $router->get('/employees/{employeeId}/projects/{projectId}/archive', [$projectController, 'archiveConfirmation'], 'admin');
    $router->post('/employees/{employeeId}/projects/{projectId}/archive', [$projectController, 'archive'], 'admin');

    $router->get('/employees/{employeeId}/certifications', [$certificationController, 'index'], 'authenticated_read');
    $router->get('/employees/{employeeId}/certifications/create', [$certificationController, 'create'], 'admin');
    $router->post('/employees/{employeeId}/certifications', [$certificationController, 'store'], 'admin');
    $router->get('/employees/{employeeId}/certifications/{certificationId}', [$certificationController, 'show'], 'authenticated_read');
    $router->get('/employees/{employeeId}/certifications/{certificationId}/edit', [$certificationController, 'edit'], 'admin');
    $router->post('/employees/{employeeId}/certifications/{certificationId}', [$certificationController, 'update'], 'admin');
    $router->get('/employees/{employeeId}/certifications/{certificationId}/archive', [$certificationController, 'archiveConfirmation'], 'admin');
    $router->post('/employees/{employeeId}/certifications/{certificationId}/archive', [$certificationController, 'archive'], 'admin');
    $router->get('/employees/{id}/edit', [$employeeController, 'edit'], 'admin');
    $router->post('/employees/{id}', [$employeeController, 'update'], 'admin');
    $router->get('/employees/{id}', [$employeeController, 'show'], 'authenticated_read');

    $router->get('/branches', [$branchController, 'index'], 'authenticated_read');
    $router->get('/branches/create', [$branchController, 'create'], 'admin');
    $router->post('/branches', [$branchController, 'store'], 'admin');
    $router->get('/branches/{id}/deactivate', [$branchController, 'deactivateConfirmation'], 'admin');
    $router->post('/branches/{id}/deactivate', [$branchController, 'deactivate'], 'admin');
    $router->get('/branches/{id}/edit', [$branchController, 'edit'], 'admin');
    $router->post('/branches/{id}', [$branchController, 'update'], 'admin');
    $router->get('/branches/{id}', [$branchController, 'show'], 'authenticated_read');

    $router->get('/departments', [$departmentController, 'index'], 'authenticated_read');
    $router->get('/departments/create', [$departmentController, 'create'], 'admin');
    $router->post('/departments', [$departmentController, 'store'], 'admin');
    $router->get('/departments/{id}/deactivate', [$departmentController, 'deactivateConfirmation'], 'admin');
    $router->post('/departments/{id}/deactivate', [$departmentController, 'deactivate'], 'admin');
    $router->get('/departments/{id}/edit', [$departmentController, 'edit'], 'admin');
    $router->post('/departments/{id}', [$departmentController, 'update'], 'admin');
    $router->get('/departments/{id}', [$departmentController, 'show'], 'authenticated_read');

    $router->get('/dispatch-companies', [$dispatchCompanyController, 'index'], 'authenticated_read');
    $router->get('/dispatch-companies/create', [$dispatchCompanyController, 'create'], 'admin');
    $router->post('/dispatch-companies', [$dispatchCompanyController, 'store'], 'admin');
    $router->get('/dispatch-companies/{id}/deactivate', [$dispatchCompanyController, 'deactivateConfirmation'], 'admin');
    $router->post('/dispatch-companies/{id}/deactivate', [$dispatchCompanyController, 'deactivate'], 'admin');
    $router->get('/dispatch-companies/{id}/edit', [$dispatchCompanyController, 'edit'], 'admin');
    $router->post('/dispatch-companies/{id}', [$dispatchCompanyController, 'update'], 'admin');
    $router->get('/dispatch-companies/{id}', [$dispatchCompanyController, 'show'], 'authenticated_read');

    $router->get('/dispatch-contracts/create', [$dispatchContractController, 'create'], 'admin');
    $router->post('/dispatch-contracts', [$dispatchContractController, 'store'], 'admin');
    $router->get('/dispatch-contracts/{id}/renew', [$dispatchContractController, 'renew'], 'admin');
    $router->post('/dispatch-contracts/{id}/renew', [$dispatchContractController, 'renewStore'], 'admin');
    $router->get('/dispatch-contracts/{id}/edit', [$dispatchContractController, 'edit'], 'admin');
    $router->post('/dispatch-contracts/{id}', [$dispatchContractController, 'update'], 'admin');
    $router->get('/dispatch-contracts/{id}', [$dispatchContractController, 'show'], 'authenticated_read');

    $router->get('/system-users', [$systemUserController, 'index'], 'admin');
    $router->get('/system-users/create', [$systemUserController, 'create'], 'admin');
    $router->post('/system-users', [$systemUserController, 'store'], 'admin');
    $router->get('/system-users/{id}/deactivate', [$systemUserController, 'deactivateConfirmation'], 'admin');
    $router->post('/system-users/{id}/deactivate', [$systemUserController, 'deactivate'], 'admin');
    $router->get('/system-users/{id}/activate', [$systemUserController, 'activateConfirmation'], 'admin');
    $router->post('/system-users/{id}/activate', [$systemUserController, 'activate'], 'admin');
    $router->get('/system-users/{id}/edit', [$systemUserController, 'edit'], 'admin');
    $router->post('/system-users/{id}', [$systemUserController, 'update'], 'admin');
    $router->get('/system-users/{id}', [$systemUserController, 'show'], 'admin');
};
