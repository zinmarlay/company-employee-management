<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use App\Application\DTO\EmployeeProjectInput;

interface EmployeeProjectRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listForEmployee(int $employeeId, bool $includeArchived = true): array;

    /** @return array<string, mixed>|null */
    public function findByIdForEmployee(int $employeeId, int $projectId): ?array;

    public function insert(int $employeeId, EmployeeProjectInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult;

    public function update(int $employeeId, int $projectId, EmployeeProjectInput $input, string $updatedAt): PortfolioWriteResult;

    public function archive(int $employeeId, int $projectId, string $archivedAt): PortfolioWriteResult;

    public function countActiveForEmployee(int $employeeId): int;
}

