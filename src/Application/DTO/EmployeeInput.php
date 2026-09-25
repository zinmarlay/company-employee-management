<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $firstNameKana,
        public string $lastNameKana,
        public string $email,
        public ?string $phone,
        public ?string $positionTitle,
        public int $branchId,
        public ?int $departmentId,
        public string $employeeType,
        public string $hireDate,
    ) {
    }
}
