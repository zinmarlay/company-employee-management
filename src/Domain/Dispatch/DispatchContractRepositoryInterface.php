<?php

declare(strict_types=1);

namespace App\Domain\Dispatch;

use App\Application\DTO\DispatchContractInput;

interface DispatchContractRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    /** @return array<int, array<string, mixed>> */
    public function findHistoryByEmployeeId(int $employeeId): array;

    /** @return array<int, array<string, mixed>> */
    public function findByCompanyId(int $companyId): array;

    public function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $exceptId = null): bool;

    public function insert(DispatchContractInput $input, string $createdAt, string $updatedAt): int;

    public function update(int $id, DispatchContractInput $input, string $updatedAt): void;
}
