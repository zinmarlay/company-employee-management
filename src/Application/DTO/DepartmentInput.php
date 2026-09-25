<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DepartmentInput
{
    public function __construct(
        public int $branchId,
        public string $code,
        public string $name,
        public ?string $description,
    ) {
    }
}
