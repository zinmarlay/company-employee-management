<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\DTO\EmployeeCertificationInput;
use App\Application\DTO\EmployeeProjectInput;
use App\Application\DTO\EmployeeSkillInput;
use App\Bootstrap\Configuration;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\LazyPdoConnection;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Infrastructure\Persistence\PdoEmployeeCertificationRepository;
use App\Infrastructure\Persistence\PdoEmployeeProjectRepository;
use App\Infrastructure\Persistence\PdoSkillRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class EmployeePortfolioRepositoryIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private int $employeeId = 0;
    private int $otherEmployeeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('APP_ENV') !== 'test') {
            self::markTestSkipped('Set APP_ENV=test to enable database integration tests.');
        }

        foreach (['DB_TEST_HOST', 'DB_TEST_PORT', 'DB_TEST_DATABASE', 'DB_TEST_USERNAME', 'DB_TEST_PASSWORD', 'DB_TEST_CHARSET'] as $key) {
            if (getenv($key) === false) self::markTestSkipped(sprintf('%s is not configured.', $key));
        }

        $database = (string) getenv('DB_TEST_DATABASE');
        if (!str_ends_with($database, '_test')) self::fail('DB_TEST_DATABASE must end with _test.');

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
        $configuration = Configuration::fromEnvironment(dirname(__DIR__, 3) . '/config/app.php', [
            'APP_ENV' => 'test',
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $database,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ]);
        self::assertSame(7, (new MigrationRunner($this->pdo(), new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations')))->migrate());

        $companyId = $this->insertCompany('portfolio-' . bin2hex(random_bytes(3)));
        $branchId = $this->insertBranch($companyId);
        $departmentId = $this->insertDepartment($branchId);
        $this->employeeId = $this->insertEmployee($branchId, $departmentId, 'one');
        $this->otherEmployeeId = $this->insertEmployee($branchId, $departmentId, 'two');

        $this->connection = new LazyPdoConnection($configuration);
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) $this->resetSchema();
        parent::tearDown();
    }

    public function testPortfolioRepositoriesPreserveHistoryAndEnforceBoundaries(): void
    {
        $skills = new PdoSkillRepository($this->connection());
        $projects = new PdoEmployeeProjectRepository($this->connection());
        $certifications = new PdoEmployeeCertificationRepository($this->connection());

        $skill = $skills->assign($this->employeeId, new EmployeeSkillInput('PHP', 'advanced', 5.5, 'Backend'), '2026-09-29 01:00:00', '2026-09-29 01:00:00');
        self::assertSame('created', $skill->status);
        self::assertSame('duplicate', $skills->assign($this->employeeId, new EmployeeSkillInput('PHP', 'expert', null, null), '2026-09-29 01:01:00', '2026-09-29 01:01:00')->status);
        self::assertNull($skills->findAssignmentForEmployee($this->otherEmployeeId, (int) $skill->id));

        self::assertSame('archived', $skills->archiveAssignment($this->employeeId, (int) $skill->id, '2026-09-29 02:00:00')->status);
        self::assertSame('restored', $skills->assign($this->employeeId, new EmployeeSkillInput('PHP', 'expert', 6.0, 'Updated'), '2026-09-29 03:00:00', '2026-09-29 03:00:00')->status);
        self::assertCount(1, $skills->listForEmployee($this->employeeId));

        $firstProject = $projects->insert($this->employeeId, new EmployeeProjectInput('Migration', 'Engineer', '2026-01-01', null, 'Summary', 'Work', 'PHP'), '2026-09-29 04:00:00', '2026-09-29 04:00:00');
        $secondProject = $projects->insert($this->employeeId, new EmployeeProjectInput('Support', 'Engineer', '2026-02-01', '2026-03-01', null, null, 'MySQL'), '2026-09-29 04:01:00', '2026-09-29 04:01:00');
        self::assertSame('created', $firstProject->status);
        self::assertSame('created', $secondProject->status);
        self::assertNull($projects->findByIdForEmployee($this->otherEmployeeId, (int) $firstProject->id)['id'] ?? null);

        self::assertSame('archived', $projects->archive($this->employeeId, (int) $firstProject->id, '2026-09-29 05:00:00')->status);
        self::assertSame('archived', $projects->findByIdForEmployee($this->employeeId, (int) $firstProject->id)['status']);

        $certification = $certifications->insert($this->employeeId, new EmployeeCertificationInput('JLPT', 'JEES', '2025-07-01', null, 'N1-1', 'Japanese'), '2026-09-29 06:00:00', '2026-09-29 06:00:00');
        self::assertSame('created', $certification->status);
        self::assertSame('duplicate', $certifications->insert($this->employeeId, new EmployeeCertificationInput('JLPT', 'JEES', '2025-07-01', null, 'N1-2', null), '2026-09-29 06:01:00', '2026-09-29 06:01:00')->status);
        self::assertSame('created', $certifications->insert($this->employeeId, new EmployeeCertificationInput('JLPT', 'JEES', '2026-07-01', null, 'N1-2', null), '2026-09-29 06:02:00', '2026-09-29 06:02:00')->status);
        self::assertSame('archived', $certifications->archive($this->employeeId, (int) $certification->id, '2026-09-29 07:00:00')->status);
        self::assertSame('archived', $certifications->findByIdForEmployee($this->employeeId, (int) $certification->id)['status']);

        $this->pdo()->prepare("UPDATE employees SET status = 'inactive' WHERE id = :id")->execute(['id' => $this->employeeId]);
        self::assertSame('inactive', $projects->insert($this->employeeId, new EmployeeProjectInput('Blocked', 'Engineer', '2026-01-01', null, null, null, null), '2026-09-29 08:00:00', '2026-09-29 08:00:00')->status);
        self::assertSame('inactive', $skills->archiveAssignment($this->employeeId, (int) $skill->id, '2026-09-29 08:01:00')->status);
    }

    private LazyPdoConnection $connection;

    private function pdo(): PDO
    {
        return $this->pdo ?? throw new \LogicException('PDO is not initialized.');
    }

    private function connection(): LazyPdoConnection
    {
        return $this->connection;
    }

    private function insertCompany(string $code): int
    {
        $statement = $this->pdo()->prepare('INSERT INTO companies (code, name, created_at, updated_at) VALUES (:code, :name, :created_at, :updated_at)');
        $statement->execute(['code' => $code, 'name' => 'Portfolio Company', 'created_at' => '2026-09-29 00:00:00', 'updated_at' => '2026-09-29 00:00:00']);
        return (int) $this->pdo()->lastInsertId();
    }

    private function insertBranch(int $companyId): int
    {
        $statement = $this->pdo()->prepare("INSERT INTO branches (company_id, code, name, city, address, phone, status, created_at, updated_at) VALUES (:company_id, 'TOKYO', 'Tokyo', 'Tokyo', 'Address', '03-0000-0000', 'active', :created_at, :updated_at)");
        $statement->execute(['company_id' => $companyId, 'created_at' => '2026-09-29 00:00:00', 'updated_at' => '2026-09-29 00:00:00']);
        return (int) $this->pdo()->lastInsertId();
    }

    private function insertDepartment(int $branchId): int
    {
        $statement = $this->pdo()->prepare("INSERT INTO departments (branch_id, code, name, description, status, created_at, updated_at) VALUES (:branch_id, 'DEV', 'Development', NULL, 'active', :created_at, :updated_at)");
        $statement->execute(['branch_id' => $branchId, 'created_at' => '2026-09-29 00:00:00', 'updated_at' => '2026-09-29 00:00:00']);
        return (int) $this->pdo()->lastInsertId();
    }

    private function insertEmployee(int $branchId, int $departmentId, string $suffix): int
    {
        $statement = $this->pdo()->prepare("INSERT INTO employees (branch_id, department_id, employee_code, first_name, last_name, first_name_kana, last_name_kana, email, phone, position_title, employee_type, hire_date, status, created_at, updated_at) VALUES (:branch_id, :department_id, :code, 'Taro', 'Example', 'タロウ', 'イグザンプル', :email, NULL, NULL, 'permanent', '2026-01-01', 'active', :created_at, :updated_at)");
        $statement->execute(['branch_id' => $branchId, 'department_id' => $departmentId, 'code' => 'EMP-' . $suffix, 'email' => $suffix . '@portfolio.test', 'created_at' => '2026-09-29 00:00:00', 'updated_at' => '2026-09-29 00:00:00']);
        return (int) $this->pdo()->lastInsertId();
    }

    private function resetSchema(): void
    {
        $pdo = $this->pdo;
        if (!$pdo instanceof PDO) return;
        foreach (['employee_certifications', 'employee_projects', 'employee_skills', 'skills', 'dispatch_contracts', 'dispatch_companies', 'employee_code_sequences', 'employees', 'departments', 'branches', 'companies', 'schema_migrations'] as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table);
        }
    }
}

