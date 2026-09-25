<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Employee;

use App\Application\DTO\EmployeeInput;
use App\Application\Employee\EmployeeService;
use App\Application\Support\Clock;
use App\Application\Validation\EmployeeInputValidator;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Organization\BranchReadRepositoryInterface;
use App\Domain\Organization\DepartmentReadRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class EmployeeServiceTest extends TestCase
{
    public function testCreateRejectsDepartmentFromAnotherBranch(): void
    {
        $employees = new InMemoryEmployeeRepository();
        $service = $this->service($employees);

        $result = $service->createEmployee($this->validInput([
            'branch_id' => '1',
            'department_id' => '2',
        ]));

        self::assertFalse($result['success']);
        self::assertSame(
            'Select a department from the selected branch.',
            $result['errors']['department_id'],
        );
        self::assertSame(0, count($employees->inserted));
    }

    public function testCreateStartsEmployeeAsActiveAndUsesUtcClock(): void
    {
        $employees = new InMemoryEmployeeRepository();
        $service = $this->service($employees);

        $result = $service->createEmployee($this->validInput([
            'department_id' => '',
        ]));

        self::assertTrue($result['success']);
        self::assertSame(2, $result['id']);
        self::assertSame('active', $employees->inserted[0]['status']);
        self::assertSame('2026-09-24 01:02:03', $employees->inserted[0]['created_at']);
        self::assertSame('2026-09-24 01:02:03', $employees->inserted[0]['updated_at']);
    }

    public function testDeactivationIsIdempotentForInactiveEmployee(): void
    {
        $employees = new InMemoryEmployeeRepository();
        $employees->rows[1]['status'] = 'inactive';
        $service = $this->service($employees);

        $result = $service->deactivateEmployee(1);

        self::assertSame('already-inactive', $result['status']);
        self::assertSame(0, $employees->deactivationCount);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function validInput(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'first_name_kana' => 'TARO',
            'last_name_kana' => 'YAMADA',
            'email' => 'taro@example.test',
            'phone' => '',
            'position_title' => 'Engineer',
            'branch_id' => '1',
            'department_id' => '1',
            'employee_type' => 'permanent',
            'hire_date' => '2026-09-24',
        ], $overrides);
    }

    private function service(InMemoryEmployeeRepository $employees): EmployeeService
    {
        return new EmployeeService(
            $employees,
            new InMemoryBranchRepository(),
            new InMemoryDepartmentRepository(),
            new EmployeeInputValidator(),
            new FixedClock(),
        );
    }
}

final class FixedClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-24 01:02:03', new DateTimeZone('UTC'));
    }
}

final class InMemoryBranchRepository implements BranchReadRepositoryInterface
{
    public function listActive(): array
    {
        return [$this->findById(1)];
    }

    public function findById(int $id): ?array
    {
        return $id === 1 ? [
            'id' => 1,
            'code' => 'TOKYO',
            'name' => 'Tokyo',
            'city' => 'Tokyo',
            'status' => 'active',
        ] : null;
    }
}

final class InMemoryDepartmentRepository implements DepartmentReadRepositoryInterface
{
    public function listActive(): array
    {
        return [
            $this->findById(1),
            $this->findById(2),
        ];
    }

    public function findById(int $id): ?array
    {
        return match ($id) {
            1 => ['id' => 1, 'branch_id' => 1, 'code' => 'ENG', 'name' => 'Engineering', 'status' => 'active'],
            2 => ['id' => 2, 'branch_id' => 2, 'code' => 'OSK', 'name' => 'Osaka', 'status' => 'active'],
            default => null,
        };
    }
}

final class InMemoryEmployeeRepository implements EmployeeRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [
        1 => [
            'id' => 1,
            'branch_id' => 1,
            'department_id' => 1,
            'employee_code' => 'EMP000001',
            'first_name' => 'Existing',
            'last_name' => 'Employee',
            'first_name_kana' => 'EXISTING',
            'last_name_kana' => 'EMPLOYEE',
            'email' => 'existing@example.test',
            'phone' => null,
            'position_title' => null,
            'employee_type' => 'permanent',
            'hire_date' => '2026-01-01',
            'status' => 'active',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ],
    ];

    /** @var array<int, array<string, mixed>> */
    public array $inserted = [];
    public int $deactivationCount = 0;

    public function listBasic(int $limit): array
    {
        return [];
    }

    public function findById(int $id): ?array
    {
        return $this->rows[$id] ?? null;
    }

    public function employeeCodeExists(string $code, ?int $exceptId = null): bool
    {
        foreach ($this->rows as $id => $row) {
            if ($id !== $exceptId && $row['employee_code'] === $code) {
                return true;
            }
        }

        return false;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        foreach ($this->rows as $id => $row) {
            if ($id !== $exceptId && $row['email'] === $email) {
                return true;
            }
        }

        return false;
    }

    public function insert(EmployeeInput $input, string $createdAt, string $updatedAt): int
    {
        $row = [
            'id' => 2,
            'branch_id' => $input->branchId,
            'department_id' => $input->departmentId,
            'employee_code' => 'EMP000002',
            'email' => $input->email,
            'status' => 'active',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
        $this->inserted[] = $row;
        $this->rows[2] = $row;

        return 2;
    }

    public function update(int $id, EmployeeInput $input, string $updatedAt): void
    {
        $this->rows[$id]['updated_at'] = $updatedAt;
    }

    public function deactivate(int $id, string $updatedAt): bool
    {
        if (($this->rows[$id]['status'] ?? null) !== 'active') {
            return false;
        }

        $this->rows[$id]['status'] = 'inactive';
        $this->rows[$id]['updated_at'] = $updatedAt;
        $this->deactivationCount++;

        return true;
    }
}
