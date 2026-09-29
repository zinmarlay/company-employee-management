<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeProjectInput
{
    public function __construct(
        public string $projectName,
        public string $role,
        public string $startDate,
        public ?string $endDate,
        public ?string $description,
        public ?string $responsibilities,
        public ?string $technologies,
    ) {
    }
}

