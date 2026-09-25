<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use RuntimeException;

final class EmployeeCodeSequenceExhaustedException extends RuntimeException
{
}
