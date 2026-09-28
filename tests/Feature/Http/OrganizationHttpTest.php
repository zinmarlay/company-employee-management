<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Application\DTO\BranchInput;
use App\Application\DTO\BranchMetadataInput;
use App\Application\DTO\DepartmentInput;
use App\Application\Organization\BranchService;
use App\Application\Organization\DepartmentService;
use App\Application\Support\Clock;
use App\Application\Validation\BranchInputValidator;
use App\Application\Validation\DepartmentInputValidator;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Request;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class OrganizationHttpTest extends TestCase
{
    public function testBranchAndDepartmentWorkflowsUseExpectedHttpSemantics(): void
    {
        $branches = new OrganizationHttpBranchRepository();
        $departments = new OrganizationHttpDepartmentRepository();
        $clock = new OrganizationHttpClock();
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views');
        $branchController = new BranchController($views, new BranchService($branches, $departments, new BranchInputValidator(), $clock));
        $departmentController = new DepartmentController($views, new DepartmentService($departments, $branches, new DepartmentInputValidator(), $clock));
        $router = new Router();
        $router->get('/branches', [$branchController, 'index']);
        $router->get('/branches/create', [$branchController, 'create']);
        $router->post('/branches', [$branchController, 'store']);
        $router->get('/branches/{id}/edit', [$branchController, 'edit']);
        $router->post('/branches/{id}', [$branchController, 'update']);
        $router->get('/branches/{id}/deactivate', [$branchController, 'deactivateConfirmation']);
        $router->post('/branches/{id}/deactivate', [$branchController, 'deactivate']);
        $router->get('/branches/{id}', [$branchController, 'show']);
        $router->get('/departments', [$departmentController, 'index']);
        $router->get('/departments/create', [$departmentController, 'create']);
        $router->post('/departments', [$departmentController, 'store']);
        $router->get('/departments/{id}', [$departmentController, 'show']);
        $kernel = new HttpKernel($router, [], new ExceptionResponder(false));

        $list = $kernel->handle(Request::fromValues('GET', '/branches'));
        self::assertSame(200, $list->statusCode());
        self::assertStringContainsString('Tokyo Branch', $list->body());

        $createForm = $kernel->handle(Request::fromValues('GET', '/branches/create'));
        self::assertSame(200, $createForm->statusCode());
        self::assertSame(1, substr_count($createForm->body(), 'name="prefecture_code"'));
        self::assertStringContainsString('value="TOKYO"', $createForm->body());
        self::assertStringNotContainsString('name="code"', $createForm->body());
        self::assertStringNotContainsString('name="name"', $createForm->body());

        $created = $kernel->handle(Request::fromValues('POST', '/branches', [], [
            'company_id' => '1',
            'prefecture_code' => 'OSAKA',
            'code' => 'FORGED',
            'name' => 'Forged name',
            'city' => '大阪市北区',
            'address' => 'Osaka address',
            'phone' => '06-0000-0000',
        ]));
        self::assertSame(303, $created->statusCode());
        self::assertSame('/branches/2', $created->header('Location'));
        self::assertSame('OSAKA', $branches->rows[2]['code']);
        self::assertSame('大阪支店', $branches->rows[2]['name']);
        $branches->rows[2]['status'] = 'inactive';

        $edit = $kernel->handle(Request::fromValues('GET', '/branches/2/edit'));
        self::assertSame(200, $edit->statusCode());
        self::assertStringContainsString('大阪支店', $edit->body());
        self::assertStringNotContainsString('name="code"', $edit->body());
        self::assertSame(303, $kernel->handle(Request::fromValues('POST', '/branches/2', [], [
            'company_id' => '999',
            'prefecture_code' => 'TOKYO',
            'code' => 'FORGED',
            'name' => 'Forged name',
            'city' => '大阪市中央区',
            'address' => 'Updated address',
            'phone' => '06-9999-9999',
        ]))->statusCode());
        self::assertSame('OSAKA', $branches->rows[2]['code']);
        self::assertSame('大阪支店', $branches->rows[2]['name']);
        self::assertSame('大阪市中央区', $branches->rows[2]['city']);

        $departmentForm = $kernel->handle(Request::fromValues('GET', '/departments/create'));
        self::assertSame(200, $departmentForm->statusCode());
        self::assertStringContainsString('Create department', $departmentForm->body());

        $invalidDepartment = $kernel->handle(Request::fromValues('POST', '/departments', [], [
            'branch_id' => '2',
            'code' => 'SALES',
            'name' => 'Sales',
            'description' => '',
        ]));
        self::assertSame(422, $invalidDepartment->statusCode());
        self::assertStringContainsString('Select an active branch.', $invalidDepartment->body());

        self::assertSame(303, $kernel->handle(Request::fromValues('POST', '/branches/1/deactivate'))->statusCode());
        self::assertSame(404, $kernel->handle(Request::fromValues('GET', '/branches/999'))->statusCode());
        self::assertSame(405, $kernel->handle(Request::fromValues('PUT', '/branches'))->statusCode());
    }
}

final class OrganizationHttpClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-25 00:00:00', new DateTimeZone('UTC'));
    }
}

final class OrganizationHttpBranchRepository implements BranchRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [1 => ['id' => 1, 'company_id' => 1, 'code' => 'TOKYO', 'name' => 'Tokyo Branch', 'city' => 'Tokyo', 'address' => 'Tokyo address', 'phone' => '03-0000-0000', 'status' => 'active', 'company_code' => 'COMPANY', 'company_name' => 'Company', 'department_count' => 1, 'employee_count' => 0]];
    public function listManagement(int $limit): array { return array_values($this->rows); }
    public function listActive(): array { return array_values(array_filter($this->rows, static fn (array $row): bool => $row['status'] === 'active')); }
    public function listCompanies(): array { return [['id' => 1, 'code' => 'COMPANY', 'name' => 'Company']]; }
    public function findCompanyById(int $id): ?array { return $id === 1 ? ['id' => 1, 'code' => 'COMPANY', 'name' => 'Company'] : null; }
    public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
    public function codeExists(int $companyId, string $code, ?int $exceptId = null): bool { foreach ($this->rows as $id => $row) if ($id !== $exceptId && $row['company_id'] === $companyId && $row['code'] === $code) return true; return false; }
    public function insert(BranchInput $input, string $createdAt, string $updatedAt): int { $id = max(array_keys($this->rows)) + 1; $this->rows[$id] = ['id' => $id, 'company_id' => $input->companyId, 'code' => $input->code, 'name' => $input->name, 'city' => $input->city, 'address' => $input->address, 'phone' => $input->phone, 'status' => 'active', 'company_code' => 'COMPANY', 'company_name' => 'Company', 'department_count' => 0, 'employee_count' => 0, 'created_at' => $createdAt, 'updated_at' => $updatedAt]; return $id; }
    public function update(int $id, BranchInput $input, string $updatedAt): void { $this->rows[$id] = array_merge($this->rows[$id], ['code' => $input->code, 'name' => $input->name, 'city' => $input->city, 'address' => $input->address, 'phone' => $input->phone, 'updated_at' => $updatedAt]); }
    public function updateMetadata(int $id, BranchMetadataInput $input, string $updatedAt): void { $this->rows[$id] = array_merge($this->rows[$id], ['city' => $input->city, 'address' => $input->address, 'phone' => $input->phone, 'updated_at' => $updatedAt]); }
    public function deactivate(int $id, string $updatedAt): bool { if ($this->rows[$id]['status'] !== 'active') return false; $this->rows[$id]['status'] = 'inactive'; return true; }
}

final class OrganizationHttpDepartmentRepository implements DepartmentRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [1 => ['id' => 1, 'branch_id' => 1, 'code' => 'DEV', 'name' => 'Development', 'description' => null, 'status' => 'active', 'branch_code' => 'TOKYO', 'branch_name' => 'Tokyo Branch', 'branch_status' => 'active', 'company_code' => 'COMPANY', 'company_name' => 'Company', 'employee_count' => 0]];
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
