<?php

declare(strict_types=1);

use App\Http\Controllers\SetupController;
use App\Http\Routing\Router;

return static function (Router $router, SetupController $setupController): void {
    $router->get('/', $setupController);
};
