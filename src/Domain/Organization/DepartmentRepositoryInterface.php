<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Application\DTO\DepartmentInput;
use App\Application\DTO\DepartmentMetadataInput;

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

    public function updateMetadata(int $id, DepartmentMetadataInput $input, string $updatedAt): bool;

    public function deactivate(int $id, string $updatedAt): bool;
}
