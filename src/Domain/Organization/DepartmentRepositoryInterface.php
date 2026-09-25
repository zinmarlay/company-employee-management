<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Application\DTO\DepartmentInput;

interface DepartmentRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listManagement(int $limit): array;

    /** @return array<int, array<string, mixed>> */
    public function listActive(): array;

    /** @return array<int, array<string, mixed>> */
    public function listByBranchId(int $branchId): array;

    /** @return array<int, array<string, mixed>> */
    public function listEmployeesByDepartment(int $departmentId): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    public function codeExists(int $branchId, string $code, ?int $exceptId = null): bool;

    public function insert(DepartmentInput $input, string $createdAt, string $updatedAt): int;

    public function update(int $id, DepartmentInput $input, string $updatedAt): void;

    public function deactivate(int $id, string $updatedAt): bool;
}
