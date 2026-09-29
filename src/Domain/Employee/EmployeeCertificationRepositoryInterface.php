<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use App\Application\DTO\EmployeeCertificationInput;

interface EmployeeCertificationRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listForEmployee(int $employeeId, bool $includeArchived = true): array;

    /** @return array<string, mixed>|null */
    public function findByIdForEmployee(int $employeeId, int $certificationId): ?array;

    public function insert(int $employeeId, EmployeeCertificationInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult;

    public function update(int $employeeId, int $certificationId, EmployeeCertificationInput $input, string $updatedAt): PortfolioWriteResult;

    public function archive(int $employeeId, int $certificationId, string $archivedAt): PortfolioWriteResult;

    public function countActiveForEmployee(int $employeeId): int;
}

