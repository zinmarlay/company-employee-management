<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260922000200CreateBranches implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE branches ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'company_id BIGINT UNSIGNED NOT NULL,'
            . 'code VARCHAR(30) NOT NULL,'
            . 'name VARCHAR(160) NOT NULL,'
            . 'city VARCHAR(120) NOT NULL,'
            . 'address VARCHAR(500) NOT NULL,'
            . 'phone VARCHAR(32) NOT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_branches_company_code (company_id, code),'
            . 'CONSTRAINT fk_branches_company FOREIGN KEY (company_id) '
            . 'REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . "CONSTRAINT chk_branches_status CHECK (status IN ('active', 'inactive'))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE branches');
    }
}
