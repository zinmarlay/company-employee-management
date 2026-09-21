<?php

declare(strict_types=1);

namespace Tests\Fixtures\IntegrationMigrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260101000001CreatePhase03Records implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE phase03_test_records ('
            . 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
            . 'label VARCHAR(191) NOT NULL'
            . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS phase03_test_records');
    }
}
