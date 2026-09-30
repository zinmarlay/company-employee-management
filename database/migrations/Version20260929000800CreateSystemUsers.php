<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260929000800CreateSystemUsers implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE system_users ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'name VARCHAR(120) NOT NULL,'
            . 'email VARCHAR(254) NOT NULL,'
            . 'password_hash VARCHAR(255) NOT NULL,'
            . "role VARCHAR(20) NOT NULL DEFAULT 'USER',"
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'last_login_at DATETIME NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_system_users_email (email),'
            . 'KEY idx_system_users_status_role (status, role),'
            . 'KEY idx_system_users_last_login_at (last_login_at),'
            . "CONSTRAINT chk_system_users_role CHECK (role IN ('ADMIN', 'USER')) ,"
            . "CONSTRAINT chk_system_users_status CHECK (status IN ('active', 'inactive'))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE system_users');
    }
}
