<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Dispatch;

use App\Application\DTO\DispatchCompanyInput;
use App\Application\DTO\DispatchContractInput;
use App\Application\DTO\EmployeeInput;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Application\Dispatch\DispatchContractService;
use App\Application\Support\Clock;
use App\Application\Validation\DispatchContractInputValidator;
use App\Domain\Dispatch\DispatchCompanyRepositoryInterface;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DispatchContractServiceTest extends TestCase
{
    public function testPermanentEmployeeIsRejected(): void
    {
        $contracts = new FakeContractRepository();
        $service = $this->service($contracts);

        $result = $service->createContract($this->input(['employee_id' => '2']));

        self::assertFalse($result['success']);
        self::assertSame('Only dispatched employees can receive a dispatch contract.', $result['errors']['employee_id']);
        self::assertCount(0, $contracts->inserted);
    }

    public function testInactiveOrMissingCompanyIsRejected(): void
    {
        $service = $this->service(new FakeContractRepository());

        $missing = $service->createContract($this->input(['dispatch_company_id' => '99']));
        self::assertSame('Select an existing dispatch company.', $missing['errors']['dispatch_company_id']);

        $inactive = $service->createContract($this->input(['dispatch_company_id' => '2']));
        self::assertSame('Select an active dispatch company.', $inactive['errors']['dispatch_company_id']);
    }

    public function testOverlapIsRejectedAndAdjacentPeriodIsAccepted(): void
    {
        $contracts = new FakeContractRepository();
        $service = $this->service($contracts);

        $overlap = $service->createContract($this->input([
            'start_date' => '2026-03-01',
            'end_date' => '2026-05-31',
        ]));
        self::assertSame('This contract overlaps an existing period.', $overlap['errors']['start_date']);

        $adjacent = $service->createContract($this->input([
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
        ]));
        self::assertTrue($adjacent['success']);
    }

    public function testRenewalInsertsNewRowAndPreservesSource(): void
    {
        $contracts = new FakeContractRepository();
        $service = $this->service($contracts);
        $sourceBefore = $contracts->rows[1];

        $result = $service->renewContract(1, $this->input([
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
        ]));

        self::assertTrue($result['success']);
        self::assertSame($sourceBefore, $contracts->rows[1]);
        self::assertCount(1, $contracts->inserted);
        self::assertSame(2, $result['id']);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function input(array $overrides = []): array
    {
        return array_replace([
            'employee_id' => '1',
            'dispatch_company_id' => '1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ], $overrides);
    }

    private function service(FakeContractRepository $contracts): DispatchContractService
    {
        return new DispatchContractService(
            $contracts,
            new FakeDispatchCompanyRepository(),
            new FakeEmployeeRepository(),
            new DispatchContractInputValidator(),
            new FixedDispatchClock(),
            new ContractExpirationClassifier(),
        );
    }
}

final class FixedDispatchClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-24 01:02:03', new DateTimeZone('UTC'));
    }
}

final class FakeDispatchCompanyRepository implements DispatchCompanyRepositoryInterface
{
    public function listBasic(int $limit): array { return array_values($this->rows); }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function insert(DispatchCompanyInput $input, string $createdAt, string $updatedAt): int { return 1; }
    public function update(int $id, DispatchCompanyInput $input, string $updatedAt): void {}
    public function deactivate(int $id, string $updatedAt): bool { return true; }

    /** @var array<int, array<string, mixed>> */
    private array $rows = [
        1 => ['id' => 1, 'code' => 'PARTNER-1', 'name' => 'Partner One', 'status' => 'active'],
        2 => ['id' => 2, 'code' => 'PARTNER-2', 'name' => 'Partner Two', 'status' => 'inactive'],
    ];
}

final class FakeEmployeeRepository implements EmployeeRepositoryInterface
{
    public function listBasic(int $limit): array { return array_values($this->rows); }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function employeeCodeExists(string $code, ?int $exceptId = null): bool { return false; }
    public function emailExists(string $email, ?int $exceptId = null): bool { return false; }
    public function insert(EmployeeInput $input, string $createdAt, string $updatedAt): int { return 1; }
    public function update(int $id, EmployeeInput $input, string $updatedAt): void {}
    public function deactivate(int $id, string $updatedAt): bool { return true; }

    /** @var array<int, array<string, mixed>> */
    private array $rows = [
        1 => ['id' => 1, 'employee_code' => 'EMP-1', 'first_name' => 'Taro', 'last_name' => 'Yamada', 'employee_type' => 'dispatched'],
        2 => ['id' => 2, 'employee_code' => 'EMP-2', 'first_name' => 'Hanako', 'last_name' => 'Sato', 'employee_type' => 'permanent'],
    ];
}

final class FakeContractRepository implements DispatchContractRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [
        1 => ['id' => 1, 'employee_id' => 1, 'employee_code' => 'EMP-1', 'employee_name' => 'Yamada Taro', 'employee_type' => 'dispatched', 'dispatch_company_id' => 1, 'dispatch_company_code' => 'PARTNER-1', 'dispatch_company_name' => 'Partner One', 'dispatch_company_status' => 'active', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
    ];
    /** @var array<int, DispatchContractInput> */
    public array $inserted = [];

    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function findHistoryByEmployeeId(int $employeeId): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['employee_id'] === $employeeId)); }
    public function findByCompanyId(int $companyId): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['dispatch_company_id'] === $companyId)); }
    public function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $exceptId = null): bool
    {
        foreach ($this->rows as $id => $row) {
            if ($row['employee_id'] !== $employeeId || $exceptId === $id) continue;
            if ($startDate <= $row['end_date'] && $endDate >= $row['start_date']) return true;
        }
        return false;
    }
    public function insert(DispatchContractInput $input, string $createdAt, string $updatedAt): int
    {
        $id = count($this->rows) + 1;
        $this->inserted[] = $input;
        $this->rows[$id] = ['id' => $id, 'employee_id' => $input->employeeId, 'dispatch_company_id' => $input->dispatchCompanyId, 'start_date' => $input->startDate, 'end_date' => $input->endDate];
        return $id;
    }
    public function update(int $id, DispatchContractInput $input, string $updatedAt): void { $this->rows[$id]['start_date'] = $input->startDate; $this->rows[$id]['end_date'] = $input->endDate; }
}
