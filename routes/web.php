<?php

declare(strict_types=1);

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SetupController;
use App\Http\Routing\Router;

return static function (
    Router $router,
    SetupController $setupController,
    EmployeeController $employeeController,
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
};
