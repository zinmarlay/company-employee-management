<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Application\DTO\EmployeeInput;
use App\Application\Employee\EmployeeService;
use App\Application\Support\Clock;
use App\Application\Validation\EmployeeInputValidator;
use App\Bootstrap\Configuration;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Organization\BranchReadRepositoryInterface;
use App\Domain\Organization\DepartmentReadRepositoryInterface;
use App\Http\Controllers\EmployeeController;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Request;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class EmployeeHttpTest extends TestCase
{
    public function testEmployeeReadRoutesRenderThroughTheKernel(): void
    {
        $kernel = $this->kernel();

        $list = $kernel->handle(Request::fromValues('GET', '/employees'));
        $create = $kernel->handle(Request::fromValues('GET', '/employees/create'));
        $detail = $kernel->handle(Request::fromValues('GET', '/employees/1'));
        $edit = $kernel->handle(Request::fromValues('GET', '/employees/1/edit'));
        $deactivate = $kernel->handle(Request::fromValues('GET', '/employees/1/deactivate'));
        $missing = $kernel->handle(Request::fromValues('GET', '/employees/999'));

        self::assertSame(200, $list->statusCode());
        self::assertSame(200, $create->statusCode());
        self::assertSame(200, $detail->statusCode());
        self::assertSame(200, $edit->statusCode());
        self::assertSame(200, $deactivate->statusCode());
        self::assertSame(404, $missing->statusCode());
        self::assertStringContainsString('EMP000001', $detail->body());
        self::assertStringContainsString('Automatically assigned.', $create->body());
        self::assertStringContainsString('EMP000001', $edit->body());
        self::assertStringContainsString('&lt;Employee&gt;', $detail->body());
        self::assertStringNotContainsString('<Employee>', $detail->body());
        self::assertStringContainsString('Confirm deactivation', $deactivate->body());
    }

    public function testInvalidCreatePreservesSubmittedValuesAndEscapesThem(): void
    {
        $kernel = $this->kernel();

        $response = $kernel->handle(Request::fromValues(
            'POST',
            '/employees',
            [],
            [
                'first_name' => '<script>alert(1)</script>',
                'last_name' => 'Yamada',
                'first_name_kana' => 'タロウ',
                'last_name_kana' => 'ヤマダ',
                'email' => 'not-an-email',
                'branch_id' => '1',
                'department_id' => '',
                'employee_type' => 'permanent',
                'hire_date' => '2026-09-24',
            ],
        ));

        self::assertSame(422, $response->statusCode());
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->body());
        self::assertStringNotContainsString('<script>alert(1)</script>', $response->body());
        self::assertStringContainsString('Enter a valid email address.', $response->body());
    }

    public function testSuccessfulCreateAndUpdateUsePostRedirectGet(): void
    {
        $kernel = $this->kernel();
        $input = [
            'first_name' => 'Hanako',
            'last_name' => 'Sato',
            'first_name_kana' => 'ハナコ',
            'last_name_kana' => 'サトウ',
            'email' => 'hanako@example.test',
            'phone' => '',
            'position_title' => 'Designer',
            'branch_id' => '1',
            'department_id' => '1',
            'employee_type' => 'permanent',
            'hire_date' => '2026-09-24',
        ];

        $created = $kernel->handle(Request::fromValues('POST', '/employees', [], $input));
        $updated = $kernel->handle(Request::fromValues(
            'POST',
            '/employees/1',
            [],
            array_replace($input, [
                'employee_code' => 'EMP999999',
                'email' => 'updated@example.test',
            ]),
        ));

        self::assertSame(303, $created->statusCode());
        self::assertSame('/employees/2', $created->header('Location'));
        self::assertSame(303, $updated->statusCode());
        self::assertSame('/employees/1', $updated->header('Location'));
        $detail = $kernel->handle(Request::fromValues('GET', '/employees/1'));
        self::assertStringContainsString('EMP000001', $detail->body());
        self::assertStringNotContainsString('EMP999999', $detail->body());
    }

    public function testInvalidUpdateReturns422AndDeactivationRedirects(): void
    {
        $kernel = $this->kernel();

        $invalid = $kernel->handle(Request::fromValues(
            'POST',
            '/employees/1',
            [],
            [
                'first_name' => '<b>Changed</b>',
                'last_name' => 'Yamada',
                'first_name_kana' => 'タロウ',
                'last_name_kana' => 'ヤマダ',
                'email' => 'invalid',
                'branch_id' => '1',
                'department_id' => '',
                'employee_type' => 'permanent',
                'hire_date' => '2026-09-24',
            ],
        ));
        $deactivated = $kernel->handle(Request::fromValues(
            'POST',
            '/employees/1/deactivate',
        ));

        self::assertSame(422, $invalid->statusCode());
        self::assertStringContainsString('&lt;b&gt;Changed&lt;/b&gt;', $invalid->body());
        self::assertSame(303, $deactivated->statusCode());
        self::assertSame('/employees/1?notice=deactivated', $deactivated->header('Location'));
    }

    public function testEmployeeRouteUnsupportedMethodsReturn405(): void
    {
        $response = $this->kernel()->handle(Request::fromValues('PUT', '/employees'));

        self::assertSame(405, $response->statusCode());
        self::assertSame('GET, POST', $response->header('Allow'));
    }

    private function kernel(): HttpKernel
    {
        $configuration = Configuration::fromEnvironment(dirname(__DIR__, 3) . '/config/app.php', [
            'APP_ENV' => 'local',
            'APP_DEBUG' => false,
        ]);
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views');
        $service = new EmployeeService(
            new HttpEmployeeRepository(),
            new HttpBranchRepository(),
            new HttpDepartmentRepository(),
            new EmployeeInputValidator(),
            new HttpFixedClock(),
        );
        $controller = new EmployeeController($views, $service, $configuration);
        $router = new Router();
        $router->get('/employees', [$controller, 'index']);
        $router->get('/employees/create', [$controller, 'create']);
        $router->post('/employees', [$controller, 'store']);
        $router->get('/employees/{id}/deactivate', [$controller, 'deactivateConfirmation']);
        $router->post('/employees/{id}/deactivate', [$controller, 'deactivate']);
        $router->get('/employees/{id}/edit', [$controller, 'edit']);
        $router->post('/employees/{id}', [$controller, 'update']);
        $router->get('/employees/{id}', [$controller, 'show']);

        return new HttpKernel($router, [], new ExceptionResponder(false));
    }
}

final class HttpFixedClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-24 01:02:03', new DateTimeZone('UTC'));
    }
}

final class HttpBranchRepository implements BranchReadRepositoryInterface
{
    public function listActive(): array
    {
        return [$this->findById(1)];
    }

    public function findById(int $id): ?array
    {
        return $id === 1
            ? ['id' => 1, 'code' => 'TOKYO', 'name' => 'Tokyo', 'city' => 'Tokyo', 'status' => 'active']
            : null;
    }
}

final class HttpDepartmentRepository implements DepartmentReadRepositoryInterface
{
    public function listActive(): array
    {
        return [$this->findById(1)];
    }

    public function findById(int $id): ?array
    {
        return $id === 1
            ? ['id' => 1, 'branch_id' => 1, 'code' => 'ENG', 'name' => 'Engineering', 'status' => 'active']
            : null;
    }
}

final class HttpEmployeeRepository implements EmployeeRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [
        1 => [
            'id' => 1,
            'branch_id' => 1,
            'department_id' => 1,
            'employee_code' => 'EMP000001',
            'first_name' => 'Taro',
            'last_name' => '<Employee>',
            'first_name_kana' => 'タロウ',
            'last_name_kana' => 'エンプロイー',
            'email' => 'employee@example.test',
            'phone' => '03-0000-0000',
            'position_title' => '<Engineer>',
            'employee_type' => 'permanent',
            'hire_date' => '2026-09-24',
            'status' => 'active',
            'created_at' => '2026-09-24 01:02:03',
            'updated_at' => '2026-09-24 01:02:03',
            'branch_code' => 'TOKYO',
            'branch_name' => '<Tokyo>',
            'branch_status' => 'active',
            'department_code' => 'ENG',
            'department_name' => '<Engineering>',
            'department_status' => 'active',
        ],
    ];
    private int $nextId = 2;
    private int $nextEmployeeCode = 2;

    public function listBasic(int $limit): array
    {
        $rows = array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'employee_code' => $row['employee_code'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'branch_name' => $row['branch_name'],
                'department_name' => $row['department_name'],
                'position_title' => $row['position_title'],
                'employee_type' => $row['employee_type'],
                'status' => $row['status'],
            ],
            array_values($this->rows),
        );

        return array_slice($rows, 0, $limit);
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
        $id = $this->nextId++;
        $code = 'EMP' . str_pad((string) $this->nextEmployeeCode++, 6, '0', STR_PAD_LEFT);
        $this->rows[$id] = $this->rowFromInput($id, $input, $code, $createdAt, $updatedAt);

        return $id;
    }

    public function update(int $id, EmployeeInput $input, string $updatedAt): void
    {
        $current = $this->rows[$id];
        $this->rows[$id] = array_replace(
            $current,
            $this->rowFromInput($id, $input, (string) $current['employee_code'], (string) $current['created_at'], $updatedAt),
            [
                'status' => $current['status'],
                'created_at' => $current['created_at'],
                'updated_at' => $updatedAt,
            ],
        );
    }

    public function deactivate(int $id, string $updatedAt): bool
    {
        if (($this->rows[$id]['status'] ?? null) !== 'active') {
            return false;
        }

        $this->rows[$id]['status'] = 'inactive';
        $this->rows[$id]['updated_at'] = $updatedAt;

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFromInput(
        int $id,
        EmployeeInput $input,
        string $employeeCode,
        string $createdAt,
        string $updatedAt,
    ): array {
        return [
            'id' => $id,
            'branch_id' => $input->branchId,
            'department_id' => $input->departmentId,
            'employee_code' => $employeeCode,
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'first_name_kana' => $input->firstNameKana,
            'last_name_kana' => $input->lastNameKana,
            'email' => $input->email,
            'phone' => $input->phone,
            'position_title' => $input->positionTitle,
            'employee_type' => $input->employeeType,
            'hire_date' => $input->hireDate,
            'status' => 'active',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'branch_code' => 'TOKYO',
            'branch_name' => 'Tokyo',
            'branch_status' => 'active',
            'department_code' => 'ENG',
            'department_name' => $input->departmentId === null ? null : 'Engineering',
            'department_status' => $input->departmentId === null ? null : 'active',
        ];
    }
}
