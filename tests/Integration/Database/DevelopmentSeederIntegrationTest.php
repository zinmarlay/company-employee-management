<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\Support\Clock;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Database\Seed\DevelopmentSeeder;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class DevelopmentSeederIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;

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
        $runner = new MigrationRunner($this->pdo, new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations'));
        self::assertSame(5, $runner->migrate());
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->resetSchema();
        }

        parent::tearDown();
    }

    public function testSeedCreatesExpectedRecordsIsIdempotentAndPreservesUnrelatedData(): void
    {
        $this->insertUnrelatedCompany();
        $firstSeeder = new DevelopmentSeeder(
            $this->pdo(),
            new FixedSeedClock(new DateTimeImmutable('2026-09-25 12:00:00', new DateTimeZone('UTC'))),
        );

        $first = $firstSeeder->seed();
        $this->insertUnrelatedContract();
        $secondSeeder = new DevelopmentSeeder(
            $this->pdo(),
            new FixedSeedClock(new DateTimeImmutable('2026-10-25 12:00:00', new DateTimeZone('UTC'))),
        );
        $second = $secondSeeder->seed();

        self::assertSame(1, $first['companies']);
        self::assertSame(6, $first['dispatch_contracts']);
        self::assertSame($first, $second);
        self::assertSame(2, $this->tableCount('companies'));
        self::assertSame(2, $this->tableCount('branches'));
        self::assertSame(3, $this->tableCount('departments'));
        self::assertSame(4, $this->tableCount('employees'));
        self::assertSame(2, $this->tableCount('dispatch_companies'));
        self::assertSame(7, $this->tableCount('dispatch_contracts'));
        self::assertSame(1, $this->countWhere('companies', 'code = \'UNRELATED\''));
        self::assertSame(2, $this->countWhere('employees', "employee_type = 'permanent'"));
        self::assertSame(2, $this->countWhere('employees', "employee_type = 'dispatched'"));
        self::assertSame(6, (int) $this->pdo()->query(
            'SELECT COUNT(*) FROM dispatch_contracts c '
            . 'INNER JOIN employees e ON e.id = c.employee_id '
            . 'INNER JOIN dispatch_companies d ON d.id = c.dispatch_company_id '
            . "WHERE (e.employee_code = 'EMP002' AND d.code = 'TECH-PARTNERS') "
            . "OR (e.employee_code = 'EMP004' AND d.code = 'NEXT-STAFF')",
        )->fetchColumn());
        self::assertSame(1, $this->countWhere('dispatch_contracts', "start_date = '2025-01-01' AND end_date = '2025-03-31'"));
        self::assertSame(0, $this->countWhere('dispatch_contracts', 'start_date > end_date'));
    }

    private function insertUnrelatedCompany(): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO companies (code, name, created_at, updated_at) '
            . 'VALUES (:code, :name, :created_at, :updated_at)',
        );
        $statement->execute([
            'code' => 'UNRELATED',
            'name' => 'Unrelated User Company',
            'created_at' => '2026-09-25 00:00:00',
            'updated_at' => '2026-09-25 00:00:00',
        ]);
    }

    private function insertUnrelatedContract(): void
    {
        $employeeId = $this->pdo()->query("SELECT id FROM employees WHERE employee_code = 'EMP002'")->fetchColumn();
        $companyId = $this->pdo()->query("SELECT id FROM dispatch_companies WHERE code = 'TECH-PARTNERS'")->fetchColumn();
        $statement = $this->pdo()->prepare(
            'INSERT INTO dispatch_contracts '
            . '(employee_id, dispatch_company_id, start_date, end_date, created_at, updated_at) '
            . 'VALUES (:employee_id, :dispatch_company_id, :start_date, :end_date, :created_at, :updated_at)',
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'dispatch_company_id' => $companyId,
            'start_date' => '2025-01-01',
            'end_date' => '2025-03-31',
            'created_at' => '2026-09-25 12:00:00',
            'updated_at' => '2026-09-25 12:00:00',
        ]);
    }

    private function tableCount(string $table): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    }

    private function countWhere(string $table, string $where): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where)->fetchColumn();
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? throw new \LogicException('PDO is not initialized.');
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

final class FixedSeedClock implements Clock
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function nowUtc(): DateTimeImmutable
    {
        return $this->now;
    }
}
