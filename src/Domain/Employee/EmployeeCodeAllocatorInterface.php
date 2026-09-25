<?php

declare(strict_types=1);

namespace App\Domain\Employee;

interface EmployeeCodeAllocatorInterface
{
    /** Must be called while the employee write transaction is active. */
    public function allocate(): string;
}
