<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DispatchContractInput
{
    public function __construct(
        public int $employeeId,
        public int $dispatchCompanyId,
        public string $startDate,
        public string $endDate,
    ) {
    }
}
