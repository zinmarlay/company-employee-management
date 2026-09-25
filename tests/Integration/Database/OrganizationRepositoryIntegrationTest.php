<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\DTO\BranchInput;
use App\Application\DTO\DepartmentInput;
use App\Bootstrap\Configuration;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\LazyPdoConnection;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Domain\Organization\BranchDuplicateException;
use App\Domain\Organization\DepartmentDuplicateException;
use App\Infrastructure\Persistence\PdoBranchRepository;
use App\Infrastructure\Persistence\PdoDepartmentRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrganizationRepositoryIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?PdoBranchRepository $branches = null;
    private ?PdoDepartmentRepository $departments = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('APP_ENV') !== 'test') {
            self::markTestSkipped('Set APP_ENV=test to enable database integration tests.');
        }

        foreach (['DB_TEST_HOST', 'DB_TEST_PORT', 'DB_TEST_DATABASE', 'DB_TEST_USERNAME', 'DB_TEST_PASSWORD', 'DB_TEST_CHARSET'] as $key) {
            if (getenv($key) === false) {
                self::markTestSkipped(sprintf('%s is not configured.', $key));
            }
        }

        $database = (string) getenv('DB_TEST_DATABASE');
        if (!str_ends_with($database, '_test')) {
            self::fail('DB_TEST_DATABASE must end with _test.');
        }

        try {
            $this->pdo = (new ConnectionFactory(DatabaseConfiguration::fromEnvironment([
                'DB_HOST' => getenv('DB_TEST_HOST'),
                'DB_PORT' => getenv('DB_TEST_PORT'),
                'DB_DATABASE' => $database,
                'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
                'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
                'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
            ])))->create();
        } catch (\Throwable) {
            self::markTestSkipped('Configured MySQL test database is unavailable.');
        }

        $this->resetSchema();
        $runner = new MigrationRunner($this->pdo(), new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations'));
        self::assertSame(6, $runner->migrate());
        $environment = [
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $database,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ];
        $configuration = Configuration::fromEnvironment(dirname(__DIR__, 3) . '/config/app.php', [
            'APP_ENV' => 'test',
            ...$environment,
        ]);
        $connection = new LazyPdoConnection($configuration);
        $this->branches = new PdoBranchRepository($connection);
        $this->departments = new PdoDepartmentRepository($connection);
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->resetSchema();
        }
        parent::tearDown();
    }

    public function testOrganizationRepositoriesPreserveRelationshipsAndScopeCodes(): void
    {
        $companyOne = $this->insertCompany('COMPANY-1');
        $companyTwo = $this->insertCompany('COMPANY-2');
        $branches = $this->branches();
        $departments = $this->departments();

        $branchOne = $branches->insert(new BranchInput($companyOne, 'TOKYO', 'Tokyo', 'Tokyo', 'Tokyo address', '03-0000-0000'), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
        $branchTwo = $branches->insert(new BranchInput($companyTwo, 'TOKYO', 'Other Tokyo', 'Tokyo', 'Other address', '03-0000-0001'), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
        self::assertNotSame($branchOne, $branchTwo);

        $this->expectException(BranchDuplicateException::class);
        $branches->insert(new BranchInput($companyOne, 'TOKYO', 'Duplicate', 'Tokyo', 'Duplicate address', '03-0000-0002'), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
    }

    public function testDepartmentRepositoriesPreserveEmployeesAndSupportNonDestructiveDeactivation(): void
    {
        $companyId = $this->insertCompany('COMPANY-ORG');
        $branchId = $this->branches()->insert(new BranchInput($companyId, 'TOKYO', 'Tokyo', 'Tokyo', 'Tokyo address', '03-0000-0000'), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
        $departmentId = $this->departments()->insert(new DepartmentInput($branchId, 'DEV', 'Development', 'Team'), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
        $this->insertEmployee($branchId, $departmentId);

        self::assertSame(1, (int) $this->departments()->findById($departmentId)['employee_count']);
        self::assertCount(1, $this->departments()->listEmployeesByDepartment($departmentId));
        self::assertTrue($this->departments()->deactivate($departmentId, '2026-09-25 01:00:00'));
        self::assertSame('inactive', $this->departments()->findById($departmentId)['status']);
        self::assertSame(1, (int) $this->pdo()->query('SELECT COUNT(*) FROM employees')->fetchColumn());

        $this->expectException(DepartmentDuplicateException::class);
        $this->departments()->insert(new DepartmentInput($branchId, 'DEV', 'Duplicate', null), '2026-09-25 00:00:00', '2026-09-25 00:00:00');
    }

    private function insertCompany(string $code): int
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO companies (code, name, created_at, updated_at) VALUES (:code, :name, :created_at, :updated_at)',
        );
        $statement->execute(['code' => $code, 'name' => $code, 'created_at' => '2026-09-25 00:00:00', 'updated_at' => '2026-09-25 00:00:00']);
        return (int) $this->pdo()->lastInsertId();
    }

    private function insertEmployee(int $branchId, int $departmentId): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO employees (branch_id, department_id, employee_code, first_name, last_name, first_name_kana, last_name_kana, email, employee_type, hire_date, created_at, updated_at) '
            . 'VALUES (:branch_id, :department_id, :employee_code, :first_name, :last_name, :first_name_kana, :last_name_kana, :email, \'permanent\', :hire_date, :created_at, :updated_at)',
        );
        $statement->execute(['branch_id' => $branchId, 'department_id' => $departmentId, 'employee_code' => 'ORG-001', 'first_name' => 'Taro', 'last_name' => 'Yamada', 'first_name_kana' => 'TARO', 'last_name_kana' => 'YAMADA', 'email' => 'org-001@example.test', 'hire_date' => '2026-01-01', 'created_at' => '2026-09-25 00:00:00', 'updated_at' => '2026-09-25 00:00:00']);
    }

    private function branches(): PdoBranchRepository
    {
        return $this->branches ?? throw new \LogicException('Branch repository is not initialized.');
    }

    private function departments(): PdoDepartmentRepository
    {
        return $this->departments ?? throw new \LogicException('Department repository is not initialized.');
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? throw new \LogicException('PDO is not initialized.');
    }

    private function resetSchema(): void
    {
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_contracts');
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS employee_code_sequences');
        $this->pdo?->exec('DROP TABLE IF EXISTS employees');
        $this->pdo?->exec('DROP TABLE IF EXISTS departments');
        $this->pdo?->exec('DROP TABLE IF EXISTS branches');
        $this->pdo?->exec('DROP TABLE IF EXISTS companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS schema_migrations');
    }
}
