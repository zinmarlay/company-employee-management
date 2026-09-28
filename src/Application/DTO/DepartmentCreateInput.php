<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DepartmentCreateInput
{
    public function __construct(
        public int $branchId,
        public string $departmentCode,
        public ?string $description,
    ) {
    }
}
