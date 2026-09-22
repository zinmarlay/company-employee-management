<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260922000300CreateDepartments implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE departments ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'branch_id BIGINT UNSIGNED NOT NULL,'
            . 'code VARCHAR(30) NOT NULL,'
            . 'name VARCHAR(120) NOT NULL,'
            . 'description TEXT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_departments_branch_code (branch_id, code),'
            . 'UNIQUE KEY uq_departments_branch_id (branch_id, id),'
            . 'CONSTRAINT fk_departments_branch FOREIGN KEY (branch_id) '
            . 'REFERENCES branches (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . "CONSTRAINT chk_departments_status CHECK (status IN ('active', 'inactive'))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE departments');
    }
}
