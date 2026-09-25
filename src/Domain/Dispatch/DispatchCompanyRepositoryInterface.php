<?php

declare(strict_types=1);

namespace App\Domain\Dispatch;

use App\Application\DTO\DispatchCompanyInput;

interface DispatchCompanyRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listBasic(int $limit): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    public function insert(DispatchCompanyInput $input, string $createdAt, string $updatedAt): int;

    public function update(int $id, DispatchCompanyInput $input, string $updatedAt): void;

    public function deactivate(int $id, string $updatedAt): bool;
}
