<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260922000400CreateEmployees implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE employees ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'branch_id BIGINT UNSIGNED NOT NULL,'
            . 'department_id BIGINT UNSIGNED NULL,'
            . 'employee_code VARCHAR(40) NOT NULL,'
            . 'first_name VARCHAR(100) NOT NULL,'
            . 'last_name VARCHAR(100) NOT NULL,'
            . 'first_name_kana VARCHAR(100) NOT NULL,'
            . 'last_name_kana VARCHAR(100) NOT NULL,'
            . 'email VARCHAR(254) NOT NULL,'
            . 'phone VARCHAR(32) NULL,'
            . 'position_title VARCHAR(120) NULL,'
            . 'employee_type VARCHAR(20) NOT NULL,'
            . 'hire_date DATE NOT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_employees_employee_code (employee_code),'
            . 'UNIQUE KEY uq_employees_email (email),'
            . 'KEY idx_employees_branch_status (branch_id, status),'
            . 'KEY idx_employees_branch_department (branch_id, department_id),'
            . 'KEY idx_employees_name (last_name, first_name),'
            . 'CONSTRAINT fk_employees_branch FOREIGN KEY (branch_id) '
            . 'REFERENCES branches (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT fk_employees_branch_department '
            . 'FOREIGN KEY (branch_id, department_id) '
            . 'REFERENCES departments (branch_id, id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . "CONSTRAINT chk_employees_type CHECK (employee_type IN ('permanent', 'dispatched')),"
            . "CONSTRAINT chk_employees_status CHECK (status IN ('active', 'inactive'))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE employees');
    }
}
