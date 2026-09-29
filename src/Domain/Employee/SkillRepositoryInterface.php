<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use App\Application\DTO\EmployeeSkillInput;

interface SkillRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listForEmployee(int $employeeId, bool $includeArchived = true): array;

    /** @return array<string, mixed>|null */
    public function findAssignmentForEmployee(int $employeeId, int $assignmentId): ?array;

    /** @return array<string, mixed>|null */
    public function findSkillByName(string $name): ?array;

    public function assign(int $employeeId, EmployeeSkillInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult;

    public function updateAssignment(int $employeeId, int $assignmentId, EmployeeSkillInput $input, string $updatedAt): PortfolioWriteResult;

    public function archiveAssignment(int $employeeId, int $assignmentId, string $archivedAt): PortfolioWriteResult;

    public function countActiveForEmployee(int $employeeId): int;
}

