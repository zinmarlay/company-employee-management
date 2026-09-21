<?php

declare(strict_types=1);

namespace App\Http\Routing;

use RuntimeException;

final class MethodNotAllowedException extends RuntimeException
{
    /**
     * @param array<int, string> $allowedMethods
     */
    public function __construct(
        private readonly array $allowedMethods,
        string $path,
    ) {
        parent::__construct('The requested method is not allowed for path: ' . $path);
    }

    /**
     * @return array<int, string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
