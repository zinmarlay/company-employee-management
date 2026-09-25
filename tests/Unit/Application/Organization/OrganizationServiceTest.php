<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Application\DTO\BranchInput;
use App\Application\DTO\DepartmentInput;
use App\Application\Organization\BranchService;
use App\Application\Organization\DepartmentService;
use App\Application\Support\Clock;
use App\Application\Validation\BranchInputValidator;
use App\Application\Validation\DepartmentInputValidator;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class OrganizationServiceTest extends TestCase
{
    public function testBranchCreateChecksScopedDuplicateAndStartsActive(): void
    {
        $branches = new OrganizationBranchRepositoryFake();
        $service = new BranchService($branches, new OrganizationDepartmentRepositoryFake(), new BranchInputValidator(), new OrganizationClock());

        $duplicate = $service->createBranch($this->branchInput(['code' => 'TOKYO']));
        self::assertFalse($duplicate['success']);
        self::assertSame('A branch with this code already exists for the selected company.', $duplicate['errors']['code']);

        $created = $service->createBranch($this->branchInput(['code' => 'KYOTO']));
        self::assertTrue($created['success']);
        self::assertSame('active', $branches->rows[$created['id']]['status']);
    }

    public function testDepartmentCreateRejectsInactiveParent(): void
    {
        $departments = new OrganizationDepartmentRepositoryFake();
        $branches = new OrganizationBranchRepositoryFake();
        $branches->rows[2]['status'] = 'inactive';
        $service = new DepartmentService($departments, $branches, new DepartmentInputValidator(), new OrganizationClock());

        $result = $service->createDepartment([
            'branch_id' => '2',
            'code' => 'NEW',
            'name' => 'New department',
            'description' => '',
        ]);

        self::assertFalse($result['success']);
        self::assertSame('Select an active branch.', $result['errors']['branch_id']);
        self::assertCount(1, $departments->rows);
    }

    public function testDeactivationDoesNotCascadeAndRepeatedDeactivationIsNoOp(): void
    {
        $branches = new OrganizationBranchRepositoryFake();
        $departments = new OrganizationDepartmentRepositoryFake();
        $branchService = new BranchService($branches, $departments, new BranchInputValidator(), new OrganizationClock());
        $departmentService = new DepartmentService($departments, $branches, new DepartmentInputValidator(), new OrganizationClock());

        self::assertSame('deactivated', $branchService->deactivateBranch(1)['status']);
        self::assertSame('active', $departments->rows[1]['status']);
        self::assertSame('already-inactive', $branchService->deactivateBranch(1)['status']);
        self::assertSame('deactivated', $departmentService->deactivateDepartment(1)['status']);
        self::assertSame('already-inactive', $departmentService->deactivateDepartment(1)['status']);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function branchInput(array $overrides = []): array
    {
        return array_replace([
            'company_id' => '1',
            'code' => 'NEW',
            'name' => 'New branch',
            'city' => 'Tokyo',
            'address' => 'Tokyo address',
            'phone' => '03-0000-0000',
        ], $overrides);
    }
}

final class OrganizationClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-25 00:00:00', new DateTimeZone('UTC'));
    }
}

final class OrganizationBranchRepositoryFake implements BranchRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [
        1 => ['id' => 1, 'company_id' => 1, 'code' => 'TOKYO', 'name' => 'Tokyo Branch', 'city' => 'Tokyo', 'address' => 'Tokyo', 'phone' => '03-0000-0000', 'status' => 'active', 'company_code' => 'COMPANY', 'company_name' => 'Company'],
        2 => ['id' => 2, 'company_id' => 1, 'code' => 'OSAKA', 'name' => 'Osaka Branch', 'city' => 'Osaka', 'address' => 'Osaka', 'phone' => '06-0000-0000', 'status' => 'active', 'company_code' => 'COMPANY', 'company_name' => 'Company'],
    ];

    public function listManagement(int $limit): array { return array_values($this->rows); }
    public function listActive(): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['status'] === 'active')); }
    public function listCompanies(): array { return [['id' => 1, 'code' => 'COMPANY', 'name' => 'Company']]; }
    public function findCompanyById(int $id): ?array { return $id === 1 ? ['id' => 1, 'code' => 'COMPANY', 'name' => 'Company'] : null; }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function codeExists(int $companyId, string $code, ?int $exceptId = null): bool { foreach ($this->rows as $id => $row) if ($id !== $exceptId && $row['company_id'] === $companyId && $row['code'] === $code) return true; return false; }
    public function insert(BranchInput $input, string $createdAt, string $updatedAt): int { $id = max(array_keys($this->rows)) + 1; $this->rows[$id] = ['id' => $id, 'company_id' => $input->companyId, 'code' => $input->code, 'name' => $input->name, 'city' => $input->city, 'address' => $input->address, 'phone' => $input->phone, 'status' => 'active', 'created_at' => $createdAt, 'updated_at' => $updatedAt]; return $id; }
    public function update(int $id, BranchInput $input, string $updatedAt): void { $this->rows[$id] = array_merge($this->rows[$id], ['code' => $input->code, 'name' => $input->name, 'city' => $input->city, 'address' => $input->address, 'phone' => $input->phone, 'updated_at' => $updatedAt]); }
    public function deactivate(int $id, string $updatedAt): bool { if ($this->rows[$id]['status'] !== 'active') return false; $this->rows[$id]['status'] = 'inactive'; return true; }
}

final class OrganizationDepartmentRepositoryFake implements DepartmentRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [1 => ['id' => 1, 'branch_id' => 1, 'code' => 'DEV', 'name' => 'Development', 'description' => null, 'status' => 'active']];
    public function listManagement(int $limit): array { return array_values($this->rows); }
    public function listActive(): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['status'] === 'active')); }
    public function listByBranchId(int $branchId): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['branch_id'] === $branchId)); }
    public function listEmployeesByDepartment(int $departmentId): array { return []; }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function codeExists(int $branchId, string $code, ?int $exceptId = null): bool { foreach ($this->rows as $id => $row) if ($id !== $exceptId && $row['branch_id'] === $branchId && $row['code'] === $code) return true; return false; }
    public function insert(DepartmentInput $input, string $createdAt, string $updatedAt): int { $id = max(array_keys($this->rows)) + 1; $this->rows[$id] = ['id' => $id, 'branch_id' => $input->branchId, 'code' => $input->code, 'name' => $input->name, 'description' => $input->description, 'status' => 'active']; return $id; }
    public function update(int $id, DepartmentInput $input, string $updatedAt): void { $this->rows[$id] = array_merge($this->rows[$id], ['code' => $input->code, 'name' => $input->name, 'description' => $input->description]); }
    public function deactivate(int $id, string $updatedAt): bool { if ($this->rows[$id]['status'] !== 'active') return false; $this->rows[$id]['status'] = 'inactive'; return true; }
}
