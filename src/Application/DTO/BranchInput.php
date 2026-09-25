<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class BranchInput
{
    public function __construct(
        public int $companyId,
        public string $code,
        public string $name,
        public string $city,
        public string $address,
        public string $phone,
    ) {
    }
}
