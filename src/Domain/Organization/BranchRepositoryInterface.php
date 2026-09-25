<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Application\DTO\BranchInput;

interface BranchRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listManagement(int $limit): array;

    /** @return array<int, array<string, mixed>> */
    public function listActive(): array;

    /** @return array<int, array<string, mixed>> */
    public function listCompanies(): array;

    /** @return array<string, mixed>|null */
    public function findCompanyById(int $id): ?array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    public function codeExists(int $companyId, string $code, ?int $exceptId = null): bool;

    public function insert(BranchInput $input, string $createdAt, string $updatedAt): int;

    public function update(int $id, BranchInput $input, string $updatedAt): void;

    public function deactivate(int $id, string $updatedAt): bool;
}
