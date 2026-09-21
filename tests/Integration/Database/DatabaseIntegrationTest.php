<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use PDO;
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
}
