<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DispatchCompanyInput
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
    ) {
    }
}
