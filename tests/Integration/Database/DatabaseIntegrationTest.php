<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class DatabaseIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;

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

        $configuration = DatabaseConfiguration::fromEnvironment([
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $testDatabase,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ]);

        try {
            $this->pdo = (new ConnectionFactory($configuration))->create();
        } catch (\Throwable $exception) {
            self::markTestSkipped('Configured MySQL test database is unavailable.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->pdo->exec('DROP TABLE IF EXISTS dispatch_contracts');
            $this->pdo->exec('DROP TABLE IF EXISTS dispatch_companies');
            $this->pdo->exec('DROP TABLE IF EXISTS employee_code_sequences');
            $this->pdo->exec('DROP TABLE IF EXISTS employees');
            $this->pdo->exec('DROP TABLE IF EXISTS departments');
            $this->pdo->exec('DROP TABLE IF EXISTS branches');
            $this->pdo->exec('DROP TABLE IF EXISTS companies');
            $this->pdo->exec('DROP TABLE IF EXISTS phase03_test_records');
            $this->pdo->exec('DROP TABLE IF EXISTS schema_migrations');
        }

        parent::tearDown();
    }

    public function testPdoUsesUtf8mb4AndPreparedStatementsHandleMultibyteText(): void
    {
        self::assertSame('utf8mb4', $this->pdo?->query('SELECT @@character_set_connection')->fetchColumn());

        $this->pdo?->exec(
            'CREATE TABLE phase03_test_records ('
            . 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
            . 'label VARCHAR(191) NOT NULL'
            . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        );
        $insert = $this->pdo?->prepare('INSERT INTO phase03_test_records (label) VALUES (:label)');
        $insert?->execute(['label' => "O'Reilly 日本語"]);

        $select = $this->pdo?->prepare('SELECT label FROM phase03_test_records WHERE label = :label');
        $select?->execute(['label' => "O'Reilly 日本語"]);

        self::assertSame("O'Reilly 日本語", $select?->fetchColumn());
    }

    public function testMigrationsAreAppliedIdempotentlyAndCanRollbackLatestBatch(): void
    {
        $runner = new MigrationRunner(
            $this->pdo,
            new MigrationDiscovery(
                dirname(__DIR__, 2) . '/Fixtures/IntegrationMigrations',
                'Tests\\Fixtures\\IntegrationMigrations\\',
            ),
        );

        self::assertSame(1, $runner->migrate());
        self::assertSame(0, $runner->migrate());
        self::assertTrue($runner->status()[0]['applied']);
        self::assertSame(1, $runner->rollback());

        $status = $runner->status();
        self::assertCount(1, $status);
        self::assertFalse($status[0]['applied']);
        self::assertNull($status[0]['batch']);
        self::assertNull($status[0]['applied_at']);
    }

    public function testDomainSchemaEnforcesOrganizationalIntegrityAndRollsBackInReverseOrder(): void
    {
        $this->resetTestSchema();
        $runner = new MigrationRunner(
            $this->pdo,
            new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations'),
        );

        self::assertSame(6, $runner->migrate());
        self::assertSame(0, $runner->migrate());
        self::assertCount(6, $runner->status());
        self::assertSame(7, (int) $this->pdo?->query(
            "SELECT COUNT(*) FROM information_schema.tables "
            . "WHERE table_schema = DATABASE() AND table_name IN "
            . "('companies', 'branches', 'departments', 'employees', 'dispatch_companies', 'dispatch_contracts', 'employee_code_sequences')",
        )->fetchColumn());

        $suffix = bin2hex(random_bytes(4));
        $companyId = $this->insertCompany('company-' . $suffix);
        $tokyoBranchId = $this->insertBranch($companyId, 'tokyo-' . $suffix);
        $osakaBranchId = $this->insertBranch($companyId, 'osaka-' . $suffix);
        $tokyoDepartmentId = $this->insertDepartment($tokyoBranchId, 'engineering-' . $suffix);
        $osakaDepartmentId = $this->insertDepartment($osakaBranchId, 'engineering-' . $suffix);

        $this->insertEmployee(
            $tokyoBranchId,
            $tokyoDepartmentId,
            'employee-' . $suffix,
            'employee-' . $suffix . '@example.test',
        );
        $this->insertEmployee(
            $osakaBranchId,
            null,
            'pending-' . $suffix,
            'pending-' . $suffix . '@example.test',
        );

        $this->assertDatabaseRejects(fn () => $this->insertCompany('company-' . $suffix));
        $this->assertDatabaseRejects(fn () => $this->insertCompanyWithoutName('missing-name-' . $suffix));
        $this->assertDatabaseRejects(fn () => $this->insertBranch($companyId, 'tokyo-' . $suffix));
        $this->assertDatabaseRejects(fn () => $this->insertDepartment($tokyoBranchId, 'engineering-' . $suffix));
        $this->assertDatabaseRejects(fn () => $this->insertEmployee(
            $osakaBranchId,
            null,
            'employee-' . $suffix,
            'duplicate-code-' . $suffix . '@example.test',
        ));
        $this->assertDatabaseRejects(fn () => $this->insertEmployee(
            $osakaBranchId,
            null,
            'another-' . $suffix,
            'employee-' . $suffix . '@example.test',
        ));
        $this->assertDatabaseRejects(fn () => $this->insertBranch(999999999, 'invalid-' . $suffix));
        $this->assertDatabaseRejects(fn () => $this->insertEmployee(
            $tokyoBranchId,
            $osakaDepartmentId,
            'cross-branch-' . $suffix,
            'cross-branch-' . $suffix . '@example.test',
        ));
        $this->assertDatabaseRejects(fn () => $this->insertBranch(
            $companyId,
            'invalid-status-' . $suffix,
            'archived',
        ));
        $this->assertDatabaseRejects(fn () => $this->insertEmployee(
            $tokyoBranchId,
            null,
            'invalid-type-' . $suffix,
            'invalid-type-' . $suffix . '@example.test',
            'contractor',
        ));

        $this->assertDatabaseRejects(fn () => $this->pdo?->prepare(
            'DELETE FROM companies WHERE id = :id',
        )->execute(['id' => $companyId]));
        $this->assertDatabaseRejects(fn () => $this->pdo?->prepare(
            'DELETE FROM branches WHERE id = :id',
        )->execute(['id' => $tokyoBranchId]));
        $this->assertDatabaseRejects(fn () => $this->pdo?->prepare(
            'DELETE FROM departments WHERE id = :id',
        )->execute(['id' => $tokyoDepartmentId]));

        self::assertSame(6, $runner->rollback());
        self::assertSame(0, (int) $this->pdo?->query(
            "SELECT COUNT(*) FROM information_schema.tables "
            . "WHERE table_schema = DATABASE() AND table_name IN "
            . "('companies', 'branches', 'departments', 'employees', 'dispatch_companies', 'dispatch_contracts', 'employee_code_sequences')",
        )->fetchColumn());
    }

    private function insertCompany(string $code): int
    {
        $statement = $this->pdo?->prepare(
            'INSERT INTO companies (code, name, created_at, updated_at) '
            . 'VALUES (:code, :name, :created_at, :updated_at)',
        );
        $statement?->execute([
            'code' => $code,
            'name' => 'Example Company',
            'created_at' => '2026-09-22 00:00:00',
            'updated_at' => '2026-09-22 00:00:00',
        ]);

        return (int) $this->pdo?->lastInsertId();
    }

    private function insertCompanyWithoutName(string $code): void
    {
        $statement = $this->pdo?->prepare(
            'INSERT INTO companies (code, created_at, updated_at) '
            . 'VALUES (:code, :created_at, :updated_at)',
        );
        $statement?->execute([
            'code' => $code,
            'created_at' => '2026-09-22 00:00:00',
            'updated_at' => '2026-09-22 00:00:00',
        ]);
    }

    private function insertBranch(int $companyId, string $code, string $status = 'active'): int
    {
        $statement = $this->pdo?->prepare(
            'INSERT INTO branches '
            . '(company_id, code, name, city, address, phone, status, created_at, updated_at) '
            . 'VALUES (:company_id, :code, :name, :city, :address, :phone, :status, :created_at, :updated_at)',
        );
        $statement?->execute([
            'company_id' => $companyId,
            'code' => $code,
            'name' => 'Example Branch',
            'city' => 'Tokyo',
            'address' => '1-1 Example',
            'phone' => '03-0000-0000',
            'status' => $status,
            'created_at' => '2026-09-22 00:00:00',
            'updated_at' => '2026-09-22 00:00:00',
        ]);

        return (int) $this->pdo?->lastInsertId();
    }

    private function insertDepartment(int $branchId, string $code): int
    {
        $statement = $this->pdo?->prepare(
            'INSERT INTO departments (branch_id, code, name, created_at, updated_at) '
            . 'VALUES (:branch_id, :code, :name, :created_at, :updated_at)',
        );
        $statement?->execute([
            'branch_id' => $branchId,
            'code' => $code,
            'name' => 'Engineering',
            'created_at' => '2026-09-22 00:00:00',
            'updated_at' => '2026-09-22 00:00:00',
        ]);

        return (int) $this->pdo?->lastInsertId();
    }

    private function insertEmployee(
        int $branchId,
        ?int $departmentId,
        string $employeeCode,
        string $email,
        string $employeeType = 'permanent',
    ): void {
        $statement = $this->pdo?->prepare(
            'INSERT INTO employees '
            . '(branch_id, department_id, employee_code, first_name, last_name, first_name_kana, '
            . 'last_name_kana, email, employee_type, hire_date, created_at, updated_at) '
            . 'VALUES (:branch_id, :department_id, :employee_code, :first_name, :last_name, '
            . ':first_name_kana, :last_name_kana, :email, :employee_type, :hire_date, :created_at, :updated_at)',
        );
        $statement?->execute([
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'employee_code' => $employeeCode,
            'first_name' => 'Taro',
            'last_name' => 'Example',
            'first_name_kana' => 'タロウ',
            'last_name_kana' => 'イグザンプル',
            'email' => $email,
            'employee_type' => $employeeType,
            'hire_date' => '2020-04-01',
            'created_at' => '2026-09-22 00:00:00',
            'updated_at' => '2026-09-22 00:00:00',
        ]);
    }

    private function assertDatabaseRejects(callable $operation): void
    {
        try {
            $operation();
        } catch (PDOException) {
            self::assertTrue(true);

            return;
        }

        self::fail('Expected the database to reject an invalid operation.');
    }

    private function resetTestSchema(): void
    {
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_contracts');
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS employee_code_sequences');
        $this->pdo?->exec('DROP TABLE IF EXISTS employees');
        $this->pdo?->exec('DROP TABLE IF EXISTS departments');
        $this->pdo?->exec('DROP TABLE IF EXISTS branches');
        $this->pdo?->exec('DROP TABLE IF EXISTS companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS phase03_test_records');
        $this->pdo?->exec('DROP TABLE IF EXISTS schema_migrations');
    }
}
