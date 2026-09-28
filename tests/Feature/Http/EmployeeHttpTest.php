<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Application\DTO\EmployeeInput;
use App\Application\DTO\EmployeeSearchCriteria;
use App\Application\DTO\EmployeeSearchResult;
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
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Request;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;
use App\Localization\Translator;
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
        self::assertStringNotContainsString('Osaka', $create->body());
        self::assertStringNotContainsString('Osaka', $edit->body());
        self::assertStringContainsString('&lt;Employee&gt;', $detail->body());
        self::assertStringNotContainsString('<Employee>', $detail->body());
        self::assertStringContainsString('Confirm deactivation', $deactivate->body());
    }

    public function testEmployeeSearchUsesGetFiltersAndPreservesHistoricalResults(): void
    {
        $kernel = $this->kernel();

        $response = $kernel->handle(Request::fromValues(
            'GET',
            '/employees',
            ['keyword' => ' Historical ', 'status' => 'inactive', 'sort' => 'name', 'direction' => 'desc'],
        ));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('EMP000003', $response->body());
        self::assertStringContainsString('value="Historical"', $response->body());
        self::assertStringContainsString('1 employees', $response->body());
        self::assertStringContainsString('value="inactive" selected', $response->body());
        self::assertStringNotContainsString('EMP000001', $response->body());
    }

    public function testEmployeeSearchHandlesMismatchedAndMalformedFiltersAsEmptySafeResults(): void
    {
        $kernel = $this->kernel();

        $mismatched = $kernel->handle(Request::fromValues(
            'GET',
            '/employees',
            ['branch_id' => '1', 'department_id' => '999'],
        ));
        $malformed = $kernel->handle(Request::fromValues(
            'GET',
            '/employees',
            ['keyword' => ['unexpected'], 'page' => '-1', 'sort' => 'raw_sql'],
        ));

        self::assertSame(200, $mismatched->statusCode());
        self::assertStringContainsString('0 employees', $mismatched->body());
        self::assertStringContainsString('/employees', $mismatched->body());
        self::assertSame(200, $malformed->statusCode());
        self::assertStringContainsString('EMP000001', $malformed->body());
    }

    public function testEmployeeSearchDepartmentChoicesFollowBranchAndKeepHistoricalOptions(): void
    {
        $kernel = $this->kernel();

        $allBranches = $kernel->handle(Request::fromValues('GET', '/employees'));
        $tokyo = $kernel->handle(Request::fromValues('GET', '/employees', ['branch_id' => '1']));
        $osaka = $kernel->handle(Request::fromValues('GET', '/employees', ['branch_id' => '2']));
        $validSelection = $kernel->handle(Request::fromValues(
            'GET',
            '/employees',
            ['branch_id' => '1', 'department_id' => '1'],
        ));
        $mismatchedSelection = $kernel->handle(Request::fromValues(
            'GET',
            '/employees',
            ['branch_id' => '1', 'department_id' => '2'],
        ));
        $allDepartmentOptions = $this->between($allBranches->body(), '<select id="employee_department_id"', '</select>');
        $tokyoDepartmentOptions = $this->between($tokyo->body(), '<select id="employee_department_id"', '</select>');
        $osakaDepartmentOptions = $this->between($osaka->body(), '<select id="employee_department_id"', '</select>');

        self::assertSame(200, $allBranches->statusCode());
        self::assertStringContainsString('<option value="1"', $allDepartmentOptions);
        self::assertStringContainsString('<option value="2"', $allDepartmentOptions);
        self::assertStringContainsString('<option value="3"', $allDepartmentOptions);

        self::assertSame(200, $tokyo->statusCode());
        self::assertStringContainsString('<option value="1"', $tokyoDepartmentOptions);
        self::assertStringNotContainsString('<option value="2"', $tokyoDepartmentOptions);
        self::assertStringNotContainsString('<option value="3"', $tokyoDepartmentOptions);

        self::assertSame(200, $osaka->statusCode());
        self::assertStringNotContainsString('<option value="1"', $osakaDepartmentOptions);
        self::assertStringContainsString('<option value="2"', $osakaDepartmentOptions);
        self::assertStringContainsString('<option value="3"', $osakaDepartmentOptions);
        self::assertStringContainsString('(inactive)', $osakaDepartmentOptions);

        self::assertStringContainsString('<option value="1" selected', $validSelection->body());
        self::assertSame(200, $mismatchedSelection->statusCode());
        self::assertStringContainsString('0 employees', $mismatchedSelection->body());
        self::assertStringNotContainsString('<option value="2" selected', $mismatchedSelection->body());
    }

    public function testEmployeeSearchIncludesProgressiveDepartmentFilterEnhancement(): void
    {
        $response = $this->kernel()->handle(Request::fromValues('GET', '/employees'));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('id="employee-search-department-data"', $response->body());
        self::assertStringContainsString("branchSelect.addEventListener('change'", $response->body());
        self::assertStringContainsString('renderDepartments(branchSelect.value, departmentSelect.value)', $response->body());
    }

    public function testEmployeeSearchOrganizationChoicesUseLocalizedCatalogLabelsAndLegacyFallbacks(): void
    {
        $kernel = $this->kernel();

        $english = $kernel->handle(Request::fromValues('GET', '/employees', ['lang' => 'en']));
        $japanese = $kernel->handle(Request::fromValues('GET', '/employees', ['lang' => 'ja']));
        $englishAgain = $kernel->handle(Request::fromValues('GET', '/employees', ['lang' => 'en', 'branch_id' => '1']));

        $englishBranchOptions = $this->between($english->body(), '<select id="employee_branch_id"', '</select>');
        $englishDepartmentOptions = $this->between($english->body(), '<select id="employee_department_id"', '</select>');
        $japaneseBranchOptions = $this->between($japanese->body(), '<select id="employee_branch_id"', '</select>');
        $japaneseDepartmentOptions = $this->between($japanese->body(), '<select id="employee_department_id"', '</select>');
        $englishResultRows = $this->between($english->body(), '<tbody>', '</tbody>');
        $japaneseResultRows = $this->between($japanese->body(), '<tbody>', '</tbody>');

        self::assertStringContainsString('Tokyo Branch', $englishBranchOptions);
        self::assertStringContainsString('Tokyo Branch · Development', $englishDepartmentOptions);
        self::assertStringContainsString('Osaka Branch (inactive)', $englishBranchOptions);
        self::assertStringContainsString('Osaka Branch · Human Resources (inactive)', $englishDepartmentOptions);
        self::assertStringContainsString('横浜支店', $englishBranchOptions);
        self::assertStringContainsString('横浜支店 · 旧部署 (inactive)', $englishDepartmentOptions);
        self::assertStringNotContainsString('東京支店', $englishBranchOptions);
        self::assertStringNotContainsString('開発部', $englishDepartmentOptions);

        self::assertStringContainsString('東京支店', $japaneseBranchOptions);
        self::assertStringContainsString('東京支店・開発部', $japaneseDepartmentOptions);
        self::assertStringContainsString('大阪支店（無効）', $japaneseBranchOptions);
        self::assertStringContainsString('大阪支店・人事部（無効）', $japaneseDepartmentOptions);

        self::assertStringContainsString('data-label="Branch">Tokyo Branch', $englishResultRows);
        self::assertStringContainsString('data-label="Department">Development', $englishResultRows);
        self::assertStringContainsString('data-label="Branch">横浜支店', $englishResultRows);
        self::assertStringContainsString('data-label="Department">旧部署', $englishResultRows);
        self::assertStringContainsString('data-label="支店">東京支店', $japaneseResultRows);
        self::assertStringContainsString('data-label="部署">開発部', $japaneseResultRows);

        self::assertStringContainsString('<option value="1"', $englishAgain->body());
        self::assertStringContainsString('2 employees', $englishAgain->body());
        self::assertStringContainsString('Tokyo Branch', $englishAgain->body());
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

    public function testInactiveEmployeeOnlyExposesReadActionsAndRemainsReadable(): void
    {
        $kernel = $this->kernel();

        $list = $kernel->handle(Request::fromValues('GET', '/employees'));
        $detail = $kernel->handle(Request::fromValues('GET', '/employees/3'));

        self::assertSame(200, $list->statusCode());
        self::assertStringContainsString('href="/employees/1/edit"', $list->body());
        self::assertStringContainsString('href="/employees/1/deactivate"', $list->body());
        self::assertStringContainsString('EMP000003', $list->body());
        self::assertStringContainsString('href="/employees/3"', $list->body());
        self::assertStringNotContainsString('href="/employees/3/edit"', $list->body());
        self::assertStringNotContainsString('href="/employees/3/deactivate"', $list->body());
        self::assertStringContainsString('EMP000003', $detail->body());
        self::assertStringContainsString('Inactive', $detail->body());
        self::assertStringNotContainsString('href="/employees/3/edit"', $detail->body());
        self::assertStringNotContainsString('href="/employees/3/deactivate"', $detail->body());
    }

    public function testInactiveEditAndUpdateRedirectWithLocalizedNoticeWithoutChangingData(): void
    {
        $kernel = $this->kernel();
        $validInput = [
            'first_name' => 'Changed',
            'last_name' => 'Inactive',
            'first_name_kana' => 'CHANGED',
            'last_name_kana' => 'INACTIVE',
            'email' => 'changed@example.test',
            'phone' => '',
            'position_title' => 'Changed title',
            'branch_id' => '1',
            'department_id' => '1',
            'employee_type' => 'permanent',
            'hire_date' => '2026-09-24',
        ];

        $edit = $kernel->handle(Request::fromValues('GET', '/employees/3/edit'));
        $updated = $kernel->handle(Request::fromValues('POST', '/employees/3', [], $validInput));
        $detail = $kernel->handle(Request::fromValues('GET', '/employees/3'));
        $englishNotice = $kernel->handle(Request::fromValues(
            'GET',
            '/employees/3',
            ['notice' => 'inactive-edit'],
        ));
        $japanese = $kernel->handle(Request::fromValues(
            'GET',
            '/employees/3',
            ['notice' => 'inactive-edit', 'lang' => 'ja'],
        ));

        self::assertSame(303, $edit->statusCode());
        self::assertSame('/employees/3?notice=inactive-edit', $edit->header('Location'));
        self::assertSame(303, $updated->statusCode());
        self::assertSame('/employees/3?notice=inactive-edit', $updated->header('Location'));
        self::assertStringContainsString('Inactive employees cannot be edited.', $englishNotice->body());
        self::assertStringContainsString('無効な社員は編集できません。', $japanese->body());
        self::assertStringContainsString('Historical', $detail->body());
        self::assertStringNotContainsString('Changed title', $detail->body());
        self::assertStringNotContainsString('changed@example.test', $detail->body());
    }

    public function testRepeatedInactiveDeactivationDoesNotChangeTimestamp(): void
    {
        $kernel = $this->kernel();

        $before = $kernel->handle(Request::fromValues('GET', '/employees/3'));
        $response = $kernel->handle(Request::fromValues('POST', '/employees/3/deactivate'));
        $after = $kernel->handle(Request::fromValues('GET', '/employees/3'));

        self::assertSame(303, $response->statusCode());
        self::assertSame('/employees/3?notice=already-inactive', $response->header('Location'));
        self::assertSame($this->between($before->body(), 'Updated', '</dd>'), $this->between($after->body(), 'Updated', '</dd>'));
    }

    private function between(string $haystack, string $start, string $end): string
    {
        $startPosition = strpos($haystack, $start);
        if ($startPosition === false) {
            return '';
        }

        $endPosition = strpos($haystack, $end, $startPosition);

        return $endPosition === false
            ? substr($haystack, $startPosition)
            : substr($haystack, $startPosition, $endPosition - $startPosition);
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
        $translator = new Translator(dirname(__DIR__, 3) . '/resources/lang');
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views', $translator);
        $service = new EmployeeService(
            new HttpEmployeeRepository(),
            new HttpBranchRepository(),
            new HttpDepartmentRepository(),
            new EmployeeInputValidator(),
            new HttpFixedClock(),
            null,
            null,
            null,
            null,
            null,
            $translator,
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

        return new HttpKernel($router, [new LocaleMiddleware($translator)], new ExceptionResponder(false));
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

    public function listForSearch(): array
    {
        return [
            $this->findById(1),
            $this->findById(2),
            $this->findById(3),
        ];
    }

    public function findById(int $id): ?array
    {
        return match ($id) {
            1 => ['id' => 1, 'code' => 'TOKYO', 'name' => 'Tokyo', 'city' => 'Tokyo', 'status' => 'active'],
            2 => ['id' => 2, 'code' => 'OSAKA', 'name' => 'Osaka', 'city' => 'Osaka', 'status' => 'inactive'],
            3 => ['id' => 3, 'code' => 'LEGACY', 'name' => '横浜支店', 'city' => '横浜', 'status' => 'inactive'],
            default => null,
        };
    }
}

final class HttpDepartmentRepository implements DepartmentReadRepositoryInterface
{
    public function listActive(): array
    {
        return [$this->findById(1)];
    }

    public function listForSearch(): array
    {
        return [
            [
                'id' => 1,
                'branch_id' => 1,
                'code' => 'DEV',
                'name' => '開発部',
                'status' => 'active',
                'branch_code' => 'TOKYO',
                'branch_name' => '東京支店',
                'branch_status' => 'active',
            ],
            [
                'id' => 2,
                'branch_id' => 2,
                'code' => 'SALES',
                'name' => '営業部',
                'status' => 'active',
                'branch_code' => 'OSAKA',
                'branch_name' => '大阪支店',
                'branch_status' => 'inactive',
            ],
            [
                'id' => 3,
                'branch_id' => 2,
                'code' => 'HR',
                'name' => '旧人事',
                'status' => 'inactive',
                'branch_code' => 'OSAKA',
                'branch_status' => 'inactive',
            ],
            [
                'id' => 4,
                'branch_id' => 3,
                'code' => 'LEGACY',
                'name' => '旧部署',
                'status' => 'inactive',
                'branch_code' => 'LEGACY',
                'branch_name' => '横浜支店',
                'branch_status' => 'inactive',
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return match ($id) {
            1 => ['id' => 1, 'branch_id' => 1, 'code' => 'DEV', 'name' => '開発部', 'status' => 'active'],
            2 => ['id' => 2, 'branch_id' => 2, 'code' => 'SALES', 'name' => '営業部', 'status' => 'active'],
            3 => ['id' => 3, 'branch_id' => 2, 'code' => 'HR', 'name' => '旧人事', 'status' => 'inactive'],
            4 => ['id' => 4, 'branch_id' => 3, 'code' => 'LEGACY', 'name' => '旧部署', 'status' => 'inactive'],
            default => null,
        };
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
            'department_code' => 'DEV',
            'department_name' => '<Engineering>',
            'department_status' => 'active',
        ],
        3 => [
            'id' => 3,
            'branch_id' => 1,
            'department_id' => 1,
            'employee_code' => 'EMP000003',
            'first_name' => 'Historical',
            'last_name' => 'Employee',
            'first_name_kana' => 'HISTORICAL',
            'last_name_kana' => 'EMPLOYEE',
            'email' => 'historical@example.test',
            'phone' => '03-0000-0003',
            'position_title' => 'Former Engineer',
            'employee_type' => 'permanent',
            'hire_date' => '2025-01-01',
            'status' => 'inactive',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'branch_code' => 'TOKYO',
            'branch_name' => 'Tokyo',
            'branch_status' => 'active',
            'department_code' => 'DEV',
            'department_name' => 'Engineering',
            'department_status' => 'active',
        ],
        4 => [
            'id' => 4,
            'branch_id' => 3,
            'department_id' => 4,
            'employee_code' => 'EMP000004',
            'first_name' => 'Legacy',
            'last_name' => 'Employee',
            'first_name_kana' => 'レガシー',
            'last_name_kana' => 'エンプロイー',
            'email' => 'legacy@example.test',
            'phone' => '045-0000-0004',
            'position_title' => 'Legacy position',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'status' => 'inactive',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'branch_code' => 'LEGACY',
            'branch_name' => '横浜支店',
            'branch_status' => 'inactive',
            'department_code' => 'LEGACY',
            'department_name' => '旧部署',
            'department_status' => 'inactive',
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

    public function search(EmployeeSearchCriteria $criteria): EmployeeSearchResult
    {
        $rows = array_values(array_filter($this->rows, static function (array $row) use ($criteria): bool {
            if ($criteria->keyword !== null) {
                $haystack = implode(' ', [
                    $row['employee_code'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['first_name_kana'],
                    $row['last_name_kana'],
                    $row['email'],
                ]);
                if (mb_stripos($haystack, $criteria->keyword) === false) {
                    return false;
                }
            }
            if ($criteria->branchId !== null && (int) $row['branch_id'] !== $criteria->branchId) {
                return false;
            }
            if ($criteria->departmentId !== null && (int) ($row['department_id'] ?? 0) !== $criteria->departmentId) {
                return false;
            }
            if ($criteria->employeeType !== null && $row['employee_type'] !== $criteria->employeeType) {
                return false;
            }
            if ($criteria->status !== null && $row['status'] !== $criteria->status) {
                return false;
            }
            return true;
        }));
        usort($rows, static function (array $left, array $right) use ($criteria): int {
            $value = static function (array $row) use ($criteria): string {
                return match ($criteria->sort) {
                    'name' => $row['last_name'] . ' ' . $row['first_name'],
                    'branch' => $row['branch_name'],
                    'department' => $row['department_name'] ?? '',
                    'employee_type' => $row['employee_type'],
                    'status' => $row['status'],
                    default => $row['employee_code'],
                };
            };
            $comparison = $value($left) <=> $value($right);
            if ($comparison === 0) {
                $comparison = $left['employee_code'] <=> $right['employee_code'];
            }
            if ($comparison === 0) {
                $comparison = $left['id'] <=> $right['id'];
            }
            return $criteria->direction === 'desc' ? -$comparison : $comparison;
        });
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $criteria->perPage));
        $page = min($criteria->page, $totalPages);
        $rows = array_slice($rows, ($page - 1) * $criteria->perPage, $criteria->perPage);

        return new EmployeeSearchResult(array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'branch_code' => $row['branch_code'],
                'branch_name' => $row['branch_name'],
                'department_code' => $row['department_code'],
                'department_name' => $row['department_name'],
                'employee_code' => $row['employee_code'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'position_title' => $row['position_title'],
                'employee_type' => $row['employee_type'],
                'status' => $row['status'],
            ],
            $rows,
        ), $total, $page, $criteria->perPage);
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

    public function update(int $id, EmployeeInput $input, string $updatedAt): bool
    {
        if (($this->rows[$id]['status'] ?? null) !== 'active') {
            return false;
        }

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

        return true;
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
            'department_code' => 'DEV',
            'department_name' => $input->departmentId === null ? null : 'Engineering',
            'department_status' => $input->departmentId === null ? null : 'active',
        ];
    }
}
