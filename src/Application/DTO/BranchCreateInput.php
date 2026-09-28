<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class BranchCreateInput
{
    public function __construct(
        public int $companyId,
        public string $prefectureCode,
        public string $city,
        public string $address,
        public string $phone,
    ) {
    }
}
