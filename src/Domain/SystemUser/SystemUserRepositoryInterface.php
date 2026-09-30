<?php

declare(strict_types=1);

namespace App\Domain\SystemUser;

use App\Application\DTO\SystemUserInput;

interface SystemUserRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function list(): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    /** @return array<string, mixed>|null */
    public function findByEmailForAuthentication(string $email): ?array;

    /** @return array<string, mixed>|null */
    public function findSafeById(int $id): ?array;

    public function emailExists(string $email, ?int $exceptId = null): bool;

    public function insert(SystemUserInput $input, string $passwordHash, string $timestamp): int;

    public function update(
        int $id,
        SystemUserInput $input,
        ?string $passwordHash,
        string $timestamp,
        int $actingUserId,
    ): void;

    public function deactivate(int $id, int $actingUserId, string $timestamp): void;

    public function activate(int $id, string $timestamp): void;

    public function recordSuccessfulLogin(int $id, string $timestamp, ?string $passwordHash): void;
}
