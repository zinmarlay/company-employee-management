<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\DTO\EmployeeInput;
use App\Bootstrap\Configuration;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\LazyPdoConnection;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Domain\Employee\EmployeeDuplicateException;
use App\Infrastructure\Persistence\PdoEmployeeRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class EmployeeRepositoryIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?PdoEmployeeRepository $repository = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('APP_ENV') !== 'test') {
            self::markTestSkipped('Set APP_ENV=test to enable database integration tests.');
        }

        $required = [
            'DB_TEST_HOST',
            'DB_TEST_PORT',
            'DB_TEST_DATABASE',
            'DB_TEST_USERNAME',
            'DB_TEST_PASSWORD',
            'DB_TEST_CHARSET',
        ];

        foreach ($required as $key) {
            if (getenv($key) === false) {
                self::markTestSkipped(sprintf('%s is not configured.', $key));
            }
        }

        $testDatabase = (string) getenv('DB_TEST_DATABASE');
        $applicationDatabase = getenv('DB_DATABASE');

        if (!str_ends_with($testDatabase, '_test')) {
            self::fail('DB_TEST_DATABASE must end with _test.');
        }

        if ($applicationDatabase !== false && $testDatabase === $applicationDatabase) {
            self::fail('DB_TEST_DATABASE must differ from DB_DATABASE.');
        }

        $environment = [
            'APP_ENV' => 'test',
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $testDatabase,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ];
        $databaseConfiguration = DatabaseConfiguration::fromEnvironment($environment);
        $configuration = Configuration::fromEnvironment(
            dirname(__DIR__, 3) . '/config/app.php',
            $environment,
        );

        try {
            $this->pdo = (new ConnectionFactory($databaseConfiguration))->create();
        } catch (\Throwable $exception) {
            self::markTestSkipped('Configured MySQL test database is unavailable.');
        }

        $pdo = $this->pdo();
        $this->resetTestSchema($pdo);
        $runner = new MigrationRunner(
            $pdo,
            new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations'),
        );
        self::assertSame(5, $runner->migrate());
        $this->repository = new PdoEmployeeRepository(new LazyPdoConnection($configuration));
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->resetTestSchema($this->pdo);
        }

        parent::tearDown();
    }

    public function testEmployeeRepositoryPersistsAndReadsTheApprovedBehaviors(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $companyId = $this->insertCompany('employee-repo-' . $suffix);
        $tokyoBranchId = $this->insertBranch($companyId, 'tokyo-' . $suffix, 'Tokyo');
        $osakaBranchId = $this->insertBranch($companyId, 'osaka-' . $suffix, 'Osaka');
        $tokyoDepartmentId = $this->insertDepartment($tokyoBranchId, 'engineering-' . $suffix);
        $osakaDepartmentId = $this->insertDepartment($osakaBranchId, 'sales-' . $suffix);
        $repository = $this->repository();

        $firstId = $repository->insert(
            $this->input([
                'branchId' => $tokyoBranchId,
                'departmentId' => $tokyoDepartmentId,
                'employeeCode' => 'EMP-' . $suffix . '-A',
                'firstName' => '太郎',
                'lastName' => '山田',
                'firstNameKana' => 'タロウ',
                'lastNameKana' => 'ヤマダ',
                'email' => "o'reilly+" . $suffix . '@example.test',
                'phone' => '03-0000-0000',
                'positionTitle' => 'Engineer',
                'hireDate' => '2026-09-24',
            ]),
            '2026-09-24 01:02:03',
            '2026-09-24 01:02:03',
        );
        $secondId = $repository->insert(
            $this->input([
                'branchId' => $osakaBranchId,
                'departmentId' => null,
                'employeeCode' => 'EMP-' . $suffix . '-B',
                'firstName' => 'Abe',
                'lastName' => 'Alpha',
                'email' => 'alpha-' . $suffix . '@example.test',
                'hireDate' => '2026-09-25',
            ]),
            '2026-09-24 01:03:03',
            '2026-09-24 01:03:03',
        );
        $thirdId = $repository->insert(
            $this->input([
                'branchId' => $tokyoBranchId,
                'departmentId' => $tokyoDepartmentId,
                'employeeCode' => 'EMP-' . $suffix . '-C',
                'firstName' => 'Zed',
                'lastName' => 'Alpha',
                'email' => 'zed-' . $suffix . '@example.test',
                'hireDate' => '2026-09-26',
            ]),
            '2026-09-24 01:04:03',
            '2026-09-24 01:04:03',
        );

        self::assertNotSame($firstId, $secondId);
        self::assertNotSame($secondId, $thirdId);

        $detail = $repository->findById($firstId);
        self::assertNotNull($detail);
        self::assertSame('山田', $detail['last_name']);
        self::assertSame("o'reilly+" . $suffix . '@example.test', $detail['email']);
        self::assertSame('Tokyo Branch', $detail['branch_name']);
        self::assertSame('Engineering', $detail['department_name']);
        self::assertSame('2026-09-24', $detail['hire_date']);
        self::assertSame('2026-09-24 01:02:03', $detail['created_at']);
        self::assertSame('2026-09-24 01:02:03', $detail['updated_at']);

        $nullDepartment = $repository->findById($secondId);
        self::assertNotNull($nullDepartment);
        self::assertNull($nullDepartment['department_id']);
        self::assertNull($nullDepartment['department_name']);
        self::assertNull($repository->findById(999999999));

        $limitedRows = $repository->listBasic(2);
        self::assertCount(2, $limitedRows);
        self::assertSame('EMP-' . $suffix . '-B', $limitedRows[0]['employee_code']);
        self::assertSame('EMP-' . $suffix . '-C', $limitedRows[1]['employee_code']);

        $repository->update(
            $firstId,
            $this->input([
                'branchId' => $tokyoBranchId,
                'departmentId' => null,
                'employeeCode' => 'EMP-' . $suffix . '-A-UPDATED',
                'firstName' => '花子',
                'lastName' => '佐藤',
                'email' => 'updated-' . $suffix . '@example.test',
                'hireDate' => '2026-10-01',
            ]),
            '2026-09-24 02:02:03',
        );
        $updated = $repository->findById($firstId);
        self::assertNotNull($updated);
        self::assertSame('EMP-' . $suffix . '-A-UPDATED', $updated['employee_code']);
        self::assertSame('佐藤', $updated['last_name']);
        self::assertNull($updated['department_id']);
        self::assertSame('2026-10-01', $updated['hire_date']);
        self::assertSame('2026-09-24 01:02:03', $updated['created_at']);
        self::assertSame('2026-09-24 02:02:03', $updated['updated_at']);

        self::assertFalse($repository->employeeCodeExists(
            'EMP-' . $suffix . '-A-UPDATED',
            $firstId,
        ));
        self::assertTrue($repository->employeeCodeExists(
            'EMP-' . $suffix . '-B',
            $firstId,
        ));
        self::assertFalse($repository->emailExists(
            'updated-' . $suffix . '@example.test',
            $firstId,
        ));
        self::assertTrue($repository->emailExists(
            'alpha-' . $suffix . '@example.test',
            $firstId,
        ));

        $this->assertDuplicateCode($repository, $tokyoBranchId, $tokyoDepartmentId, $suffix);
        $this->assertDuplicateEmail($repository, $tokyoBranchId, $tokyoDepartmentId, $suffix);
        $this->assertRejected(fn() => $repository->insert(
            $this->input([
                'branchId' => 999999999,
                'departmentId' => null,
                'employeeCode' => 'invalid-branch-' . $suffix,
                'email' => 'invalid-branch-' . $suffix . '@example.test',
            ]),
            '2026-09-24 03:00:00',
            '2026-09-24 03:00:00',
        ));
        $this->assertRejected(fn() => $repository->insert(
            $this->input([
                'branchId' => $tokyoBranchId,
                'departmentId' => $osakaDepartmentId,
                'employeeCode' => 'cross-branch-' . $suffix,
                'email' => 'cross-branch-' . $suffix . '@example.test',
            ]),
            '2026-09-24 03:01:00',
            '2026-09-24 03:01:00',
        ));

        self::assertTrue($repository->deactivate($thirdId, '2026-09-24 04:00:00'));
        $inactive = $repository->findById($thirdId);
        self::assertNotNull($inactive);
        self::assertSame('inactive', $inactive['status']);
        self::assertSame('2026-09-24 04:00:00', $inactive['updated_at']);
        self::assertFalse($repository->deactivate($thirdId, '2026-09-24 05:00:00'));
        $stillInactive = $repository->findById($thirdId);
        self::assertNotNull($stillInactive);
        self::assertSame('2026-09-24 04:00:00', $stillInactive['updated_at']);
    }

    private function assertDuplicateCode(
        PdoEmployeeRepository $repository,
        int $branchId,
        int $departmentId,
        string $suffix,
    ): void {
        try {
            $repository->insert(
                $this->input([
                    'branchId' => $branchId,
                    'departmentId' => $departmentId,
                    'employeeCode' => 'EMP-' . $suffix . '-B',
                    'email' => 'duplicate-code-' . $suffix . '@example.test',
                ]),
                '2026-09-24 03:02:00',
                '2026-09-24 03:02:00',
            );
        } catch (EmployeeDuplicateException $exception) {
            self::assertSame('employee_code', $exception->field);

            return;
        }

        self::fail('Expected a duplicate employee code conflict.');
    }

    private function assertDuplicateEmail(
        PdoEmployeeRepository $repository,
        int $branchId,
        int $departmentId,
        string $suffix,
    ): void {
        try {
            $repository->insert(
                $this->input([
                    'branchId' => $branchId,
                    'departmentId' => $departmentId,
                    'employeeCode' => 'duplicate-email-' . $suffix,
                    'email' => 'alpha-' . $suffix . '@example.test',
                ]),
                '2026-09-24 03:03:00',
                '2026-09-24 03:03:00',
            );
        } catch (EmployeeDuplicateException $exception) {
            self::assertSame('email', $exception->field);

            return;
        }

        self::fail('Expected a duplicate employee email conflict.');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function input(array $overrides = []): EmployeeInput
    {
        $values = array_replace([
            'employeeCode' => 'EMP-DEFAULT',
            'firstName' => 'Taro',
            'lastName' => 'Example',
            'firstNameKana' => 'タロウ',
            'lastNameKana' => 'イグザンプル',
            'email' => 'default@example.test',
            'phone' => null,
            'positionTitle' => null,
            'branchId' => 1,
            'departmentId' => null,
            'employeeType' => 'permanent',
            'hireDate' => '2026-09-24',
        ], $overrides);

        return new EmployeeInput(
            $values['employeeCode'],
            $values['firstName'],
            $values['lastName'],
            $values['firstNameKana'],
            $values['lastNameKana'],
            $values['email'],
            $values['phone'],
            $values['positionTitle'],
            $values['branchId'],
            $values['departmentId'],
            $values['employeeType'],
            $values['hireDate'],
        );
    }

    private function insertCompany(string $code): int
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO companies (code, name, created_at, updated_at) '
                . 'VALUES (:code, :name, :created_at, :updated_at)',
        );
        $statement->execute([
            'code' => $code,
            'name' => 'Employee Repository Company',
            'created_at' => '2026-09-24 00:00:00',
            'updated_at' => '2026-09-24 00:00:00',
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    private function insertBranch(int $companyId, string $code, string $city): int
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO branches '
                . '(company_id, code, name, city, address, phone, status, created_at, updated_at) '
                . 'VALUES (:company_id, :code, :name, :city, :address, :phone, :status, :created_at, :updated_at)',
        );
        $statement->execute([
            'company_id' => $companyId,
            'code' => $code,
            'name' => $city . ' Branch',
            'city' => $city,
            'address' => '1-1 Example',
            'phone' => '03-0000-0000',
            'status' => 'active',
            'created_at' => '2026-09-24 00:00:00',
            'updated_at' => '2026-09-24 00:00:00',
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    private function insertDepartment(int $branchId, string $code): int
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO departments (branch_id, code, name, created_at, updated_at) '
                . 'VALUES (:branch_id, :code, :name, :created_at, :updated_at)',
        );
        $statement->execute([
            'branch_id' => $branchId,
            'code' => $code,
            'name' => 'Engineering',
            'created_at' => '2026-09-24 00:00:00',
            'updated_at' => '2026-09-24 00:00:00',
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    private function assertRejected(callable $operation): void
    {
        try {
            $operation();
        } catch (PDOException) {
            self::assertTrue(true);

            return;
        }

        self::fail('Expected the database to reject the operation.');
    }

    private function resetTestSchema(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS dispatch_contracts');
        $pdo->exec('DROP TABLE IF EXISTS dispatch_companies');
        $pdo->exec('DROP TABLE IF EXISTS employees');
        $pdo->exec('DROP TABLE IF EXISTS departments');
        $pdo->exec('DROP TABLE IF EXISTS branches');
        $pdo->exec('DROP TABLE IF EXISTS companies');
        $pdo->exec('DROP TABLE IF EXISTS phase03_test_records');
        $pdo->exec('DROP TABLE IF EXISTS schema_migrations');
    }

    private function pdo(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            self::fail('The integration database connection was not initialized.');
        }

        return $this->pdo;
    }

    private function repository(): PdoEmployeeRepository
    {
        if (!$this->repository instanceof PdoEmployeeRepository) {
            self::fail('The employee repository was not initialized.');
        }

        return $this->repository;
    }
}
