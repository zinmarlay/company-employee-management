<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use RuntimeException;
use Throwable;

final class EmployeeDuplicateException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        parent::__construct(sprintf('An employee with this %s already exists.', $field), $code, $previous);
    }
}
