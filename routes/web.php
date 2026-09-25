<?php

declare(strict_types=1);

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DispatchCompanyController;
use App\Http\Controllers\DispatchContractController;
use App\Http\Controllers\SetupController;
use App\Http\Routing\Router;

return static function (
    Router $router,
    SetupController $setupController,
    EmployeeController $employeeController,
    DispatchCompanyController $dispatchCompanyController,
    DispatchContractController $dispatchContractController,
): void {
    $router->get('/', $setupController);
    $router->get('/employees', [$employeeController, 'index']);
    $router->get('/employees/create', [$employeeController, 'create']);
    $router->post('/employees', [$employeeController, 'store']);
    $router->get('/employees/{id}/deactivate', [$employeeController, 'deactivateConfirmation']);
    $router->post('/employees/{id}/deactivate', [$employeeController, 'deactivate']);
    $router->get('/employees/{id}/edit', [$employeeController, 'edit']);
    $router->post('/employees/{id}', [$employeeController, 'update']);
    $router->get('/employees/{id}', [$employeeController, 'show']);

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
