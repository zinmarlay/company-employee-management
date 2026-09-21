<?php

declare(strict_types=1);

namespace App\Http\Routing;

use RuntimeException;

final class NotFoundException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct('No route matches the requested path: ' . $path);
    }
}
