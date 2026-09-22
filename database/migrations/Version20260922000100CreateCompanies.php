<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260922000100CreateCompanies implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE companies ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'code VARCHAR(30) NOT NULL,'
            . 'name VARCHAR(160) NOT NULL,'
            . 'email VARCHAR(254) NULL,'
            . 'phone VARCHAR(32) NULL,'
            . 'address VARCHAR(500) NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_companies_code (code)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE companies');
    }
}
