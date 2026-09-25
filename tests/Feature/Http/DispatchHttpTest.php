<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Application\DTO\DispatchCompanyInput;
use App\Application\DTO\DispatchContractInput;
use App\Application\DTO\EmployeeInput;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Application\Dispatch\DispatchCompanyService;
use App\Application\Dispatch\DispatchContractService;
use App\Application\Support\Clock;
use App\Application\Validation\DispatchCompanyInputValidator;
use App\Application\Validation\DispatchContractInputValidator;
use App\Domain\Dispatch\DispatchCompanyRepositoryInterface;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Http\Controllers\DispatchCompanyController;
use App\Http\Controllers\DispatchContractController;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Request;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DispatchHttpTest extends TestCase
{
    public function testCompanyAndContractFormsUseExpectedHttpSemantics(): void
    {
        $companyRepository = new DispatchHttpCompanyRepository();
        $contractRepository = new DispatchHttpContractRepository();
        $clock = new DispatchHttpClock();
        $expiration = new ContractExpirationClassifier();
        $companyService = new DispatchCompanyService($companyRepository, $contractRepository, new DispatchCompanyInputValidator(), $clock, $expiration);
        $contractService = new DispatchContractService($contractRepository, $companyRepository, new DispatchHttpEmployeeRepository(), new DispatchContractInputValidator(), $clock, $expiration);
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views');
        $companyController = new DispatchCompanyController($views, $companyService);
        $contractController = new DispatchContractController($views, $contractService);
        $router = new Router();
        $router->get('/dispatch-companies/create', [$companyController, 'create']);
        $router->post('/dispatch-companies', [$companyController, 'store']);
        $router->get('/dispatch-contracts/create', [$contractController, 'create']);
        $router->post('/dispatch-contracts', [$contractController, 'store']);
        $router->get('/dispatch-contracts/{id}', [$contractController, 'show']);
        $kernel = new HttpKernel($router, [], new ExceptionResponder(false));

        $companyForm = $kernel->handle(Request::fromValues('GET', '/dispatch-companies/create'));
        self::assertSame(200, $companyForm->statusCode());
        self::assertStringContainsString('Create dispatch company', $companyForm->body());

        $companyCreated = $kernel->handle(Request::fromValues('POST', '/dispatch-companies', [], [
            'code' => 'PARTNER-NEW',
            'name' => 'New Partner',
            'email' => 'partner@example.test',
        ]));
        self::assertSame(303, $companyCreated->statusCode());

        $invalid = $kernel->handle(Request::fromValues('POST', '/dispatch-contracts', [], [
            'employee_id' => '2',
            'dispatch_company_id' => '1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]));
        self::assertSame(422, $invalid->statusCode());
        self::assertStringContainsString('Only dispatched employees can receive a dispatch contract.', $invalid->body());

        $created = $kernel->handle(Request::fromValues('POST', '/dispatch-contracts', [], [
            'employee_id' => '1',
            'dispatch_company_id' => '1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]));
        self::assertSame(303, $created->statusCode());
        self::assertSame('/dispatch-contracts/1', $created->header('Location'));

        $detail = $kernel->handle(Request::fromValues('GET', '/dispatch-contracts/1'));
        self::assertSame(200, $detail->statusCode());
        self::assertStringContainsString('PARTNER-1', $detail->body());

        self::assertSame(404, $kernel->handle(Request::fromValues('GET', '/dispatch-contracts/999'))->statusCode());
        self::assertSame(405, $kernel->handle(Request::fromValues('PUT', '/dispatch-contracts'))->statusCode());
    }
}

final class DispatchHttpClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-24 01:02:03', new DateTimeZone('UTC'));
    }
}

final class DispatchHttpCompanyRepository implements DispatchCompanyRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [1 => ['id' => 1, 'code' => 'PARTNER-1', 'name' => 'Partner One', 'phone' => null, 'email' => null, 'address' => null, 'status' => 'active', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']];

    public function listBasic(int $limit): array { return array_values($this->rows); }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function insert(DispatchCompanyInput $input, string $createdAt, string $updatedAt): int { $id = count($this->rows) + 1; $this->rows[$id] = ['id' => $id, 'code' => $input->code, 'name' => $input->name, 'phone' => $input->phone, 'email' => $input->email, 'address' => $input->address, 'status' => 'active', 'created_at' => $createdAt, 'updated_at' => $updatedAt]; return $id; }
    public function update(int $id, DispatchCompanyInput $input, string $updatedAt): void { $this->rows[$id] = array_merge($this->rows[$id], ['code' => $input->code, 'name' => $input->name, 'phone' => $input->phone, 'email' => $input->email, 'address' => $input->address, 'updated_at' => $updatedAt]); }
    public function deactivate(int $id, string $updatedAt): bool { $this->rows[$id]['status'] = 'inactive'; return true; }
}

final class DispatchHttpContractRepository implements DispatchContractRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function findHistoryByEmployeeId(int $employeeId): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['employee_id'] === $employeeId)); }
    public function findByCompanyId(int $companyId): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['dispatch_company_id'] === $companyId)); }
    public function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $exceptId = null): bool { foreach ($this->rows as $id => $row) if ($row['employee_id'] === $employeeId && $id !== $exceptId && $startDate <= $row['end_date'] && $endDate >= $row['start_date']) return true; return false; }
    public function insert(DispatchContractInput $input, string $createdAt, string $updatedAt): int { $id = count($this->rows) + 1; $this->rows[$id] = ['id' => $id, 'employee_id' => $input->employeeId, 'employee_code' => 'EMP000001', 'employee_name' => 'Yamada Taro', 'employee_type' => 'dispatched', 'dispatch_company_id' => $input->dispatchCompanyId, 'dispatch_company_code' => 'PARTNER-1', 'dispatch_company_name' => 'Partner One', 'dispatch_company_status' => 'active', 'start_date' => $input->startDate, 'end_date' => $input->endDate, 'created_at' => $createdAt, 'updated_at' => $updatedAt]; return $id; }
    public function update(int $id, DispatchContractInput $input, string $updatedAt): void {}
}

final class DispatchHttpEmployeeRepository implements EmployeeRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [
        1 => ['id' => 1, 'employee_code' => 'EMP000001', 'first_name' => 'Taro', 'last_name' => 'Yamada', 'employee_type' => 'dispatched'],
        2 => ['id' => 2, 'employee_code' => 'EMP000002', 'first_name' => 'Hanako', 'last_name' => 'Sato', 'employee_type' => 'permanent'],
    ];
    public function listBasic(int $limit): array { return array_values($this->rows); }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function employeeCodeExists(string $code, ?int $exceptId = null): bool { return false; }
    public function emailExists(string $email, ?int $exceptId = null): bool { return false; }
    public function insert(EmployeeInput $input, string $createdAt, string $updatedAt): int { return 1; }
    public function update(int $id, EmployeeInput $input, string $updatedAt): void {}
    public function deactivate(int $id, string $updatedAt): bool { return true; }
}
