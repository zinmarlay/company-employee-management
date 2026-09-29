<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use RuntimeException;

final class PortfolioDuplicateException extends RuntimeException
{
    public function __construct(
        public readonly string $resource,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('A duplicate portfolio record exists.', 0, $previous);
    }
}

