<?php

declare(strict_types=1);

namespace App\Security;

final readonly class AuthenticatedUser
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], (string) $row['name'], (string) $row['email'], (string) $row['role']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }
}
