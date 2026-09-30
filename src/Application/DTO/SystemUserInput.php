<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class SystemUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public ?string $password,
    ) {
    }
}
