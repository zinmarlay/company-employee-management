<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\DTO\DispatchCompanyInput;
use App\Application\DTO\DispatchContractInput;
use App\Bootstrap\Configuration;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\LazyPdoConnection;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Infrastructure\Persistence\PdoDispatchCompanyRepository;
use App\Infrastructure\Persistence\PdoDispatchContractRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class DispatchRepositoryIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?PdoDispatchCompanyRepository $companies = null;
    private ?PdoDispatchContractRepository $contracts = null;

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
        $environment = [
            'APP_ENV' => 'test',
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $database,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ];
        try {
            $this->pdo = (new ConnectionFactory(DatabaseConfiguration::fromEnvironment($environment)))->create();
        } catch (\Throwable) {
            self::markTestSkipped('Configured MySQL test database is unavailable.');
        }

        $this->resetSchema();
        $runner = new MigrationRunner($this->pdo, new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations'));
        self::assertSame(5, $runner->migrate());
        $configuration = Configuration::fromEnvironment(dirname(__DIR__, 3) . '/config/app.php', $environment);
        $connection = new LazyPdoConnection($configuration);
        $this->companies = new PdoDispatchCompanyRepository($connection);
        $this->contracts = new PdoDispatchContractRepository($connection);
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) $this->resetSchema();
        parent::tearDown();
    }

    public function testDispatchRepositoriesPersistHistoryAndOverlapQueries(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $employeeId = $this->insertDispatchedEmployee($suffix);
        $company = $this->companies();
        $contracts = $this->contracts();

        $companyId = $company->insert(
            new DispatchCompanyInput('PARTNER-' . $suffix, 'Partner ' . $suffix, '03-0000-0000', 'partner-' . $suffix . '@example.test', 'Tokyo'),
            '2026-09-24 01:00:00',
            '2026-09-24 01:00:00',
        );
        $read = $company->findById($companyId);
        self::assertNotNull($read);
        self::assertSame('Partner ' . $suffix, $read['name']);
        $company->update($companyId, new DispatchCompanyInput('PARTNER-' . $suffix, 'Updated Partner ' . $suffix, null, null, 'Osaka'), '2026-09-24 01:00:30');
        self::assertSame('Updated Partner ' . $suffix, $company->findById($companyId)['name']);

        $contractId = $contracts->insert(new DispatchContractInput($employeeId, $companyId, '2026-01-01', '2026-03-31'), '2026-09-24 01:01:00', '2026-09-24 01:01:00');
        self::assertTrue($contracts->hasOverlap($employeeId, '2026-03-01', '2026-05-31'));
        self::assertFalse($contracts->hasOverlap($employeeId, '2026-04-01', '2026-06-30'));

        $secondId = $contracts->insert(new DispatchContractInput($employeeId, $companyId, '2026-04-01', '2026-06-30'), '2026-09-24 01:02:00', '2026-09-24 01:02:00');
        self::assertNotSame($contractId, $secondId);
        self::assertCount(2, $contracts->findHistoryByEmployeeId($employeeId));
        self::assertCount(2, $contracts->findByCompanyId($companyId));
        self::assertSame('Updated Partner ' . $suffix, $contracts->findById($contractId)['dispatch_company_name']);
        $contracts->update($secondId, new DispatchContractInput($employeeId, $companyId, '2026-04-01', '2026-07-31'), '2026-09-24 01:03:00');
        self::assertSame('2026-07-31', $contracts->findById($secondId)['end_date']);

        self::assertTrue($company->deactivate($companyId, '2026-09-24 02:00:00'));
        self::assertSame('inactive', $company->findById($companyId)['status']);
        self::assertSame('inactive', $contracts->findById($contractId)['dispatch_company_status']);
    }

    private function insertDispatchedEmployee(string $suffix): int
    {
        $companyId = $this->pdo->query("SELECT id FROM companies ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($companyId === false) {
            $statement = $this->pdo->prepare('INSERT INTO companies (code, name, created_at, updated_at) VALUES (:code, :name, :created_at, :updated_at)');
            $statement->execute(['code' => 'INTERNAL-' . $suffix, 'name' => 'Internal Company', 'created_at' => '2026-09-24 00:00:00', 'updated_at' => '2026-09-24 00:00:00']);
            $companyId = $this->pdo->lastInsertId();
        }
        $statement = $this->pdo->prepare('INSERT INTO branches (company_id, code, name, city, address, phone, status, created_at, updated_at) VALUES (:company_id, :code, :name, :city, :address, :phone, \'active\', :created_at, :updated_at)');
        $statement->execute(['company_id' => $companyId, 'code' => 'BR-' . $suffix, 'name' => 'Tokyo Branch', 'city' => 'Tokyo', 'address' => 'Tokyo', 'phone' => '03-0000-0000', 'created_at' => '2026-09-24 00:00:00', 'updated_at' => '2026-09-24 00:00:00']);
        $branchId = $this->pdo->lastInsertId();
        $statement = $this->pdo->prepare('INSERT INTO employees (branch_id, employee_code, first_name, last_name, first_name_kana, last_name_kana, email, employee_type, hire_date, created_at, updated_at) VALUES (:branch_id, :employee_code, :first_name, :last_name, :first_name_kana, :last_name_kana, :email, \'dispatched\', :hire_date, :created_at, :updated_at)');
        $statement->execute(['branch_id' => $branchId, 'employee_code' => 'EMP-' . $suffix, 'first_name' => 'Taro', 'last_name' => 'Yamada', 'first_name_kana' => 'タロウ', 'last_name_kana' => 'ヤマダ', 'email' => 'employee-' . $suffix . '@example.test', 'hire_date' => '2026-01-01', 'created_at' => '2026-09-24 00:00:00', 'updated_at' => '2026-09-24 00:00:00']);
        return (int) $this->pdo->lastInsertId();
    }

    private function companies(): PdoDispatchCompanyRepository
    {
        return $this->companies ?? throw new \LogicException('Repository not initialized.');
    }

    private function contracts(): PdoDispatchContractRepository
    {
        return $this->contracts ?? throw new \LogicException('Repository not initialized.');
    }

    private function resetSchema(): void
    {
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_contracts');
        $this->pdo?->exec('DROP TABLE IF EXISTS dispatch_companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS employees');
        $this->pdo?->exec('DROP TABLE IF EXISTS departments');
        $this->pdo?->exec('DROP TABLE IF EXISTS branches');
        $this->pdo?->exec('DROP TABLE IF EXISTS companies');
        $this->pdo?->exec('DROP TABLE IF EXISTS schema_migrations');
    }
}
