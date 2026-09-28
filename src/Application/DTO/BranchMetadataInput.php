<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class BranchMetadataInput
{
    public function __construct(
        public string $city,
        public string $address,
        public string $phone,
    ) {
    }
}
