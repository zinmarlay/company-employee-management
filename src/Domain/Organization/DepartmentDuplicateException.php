<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use RuntimeException;

final class DepartmentDuplicateException extends RuntimeException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('A department with this code already exists for the selected branch.', $code, $previous);
    }
}
