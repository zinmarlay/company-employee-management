<?php

declare(strict_types=1);

namespace App\Domain\Employee;

use App\Application\DTO\EmployeeInput;
use App\Application\DTO\EmployeeSearchCriteria;
use App\Application\DTO\EmployeeSearchResult;

interface EmployeeRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listBasic(int $limit): array;

    public function search(EmployeeSearchCriteria $criteria): EmployeeSearchResult;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    public function employeeCodeExists(string $code, ?int $exceptId = null): bool;

    public function emailExists(string $email, ?int $exceptId = null): bool;

    public function insert(EmployeeInput $input, string $createdAt, string $updatedAt): int;

    /** Returns true when an active employee row was updated. */
    public function update(int $id, EmployeeInput $input, string $updatedAt): bool;

    public function deactivate(int $id, string $updatedAt): bool;
}
