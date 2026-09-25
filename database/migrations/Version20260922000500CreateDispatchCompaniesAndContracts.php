<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260922000500CreateDispatchCompaniesAndContracts implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE dispatch_companies ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'code VARCHAR(30) NOT NULL,'
            . 'name VARCHAR(160) NOT NULL,'
            . 'phone VARCHAR(32) NULL,'
            . 'email VARCHAR(254) NULL,'
            . 'address VARCHAR(500) NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_dispatch_companies_code (code),'
            . "CONSTRAINT chk_dispatch_companies_status CHECK (status IN ('active', 'inactive'))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        $pdo->exec(
            'CREATE TABLE dispatch_contracts ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'employee_id BIGINT UNSIGNED NOT NULL,'
            . 'dispatch_company_id BIGINT UNSIGNED NOT NULL,'
            . 'start_date DATE NOT NULL,'
            . 'end_date DATE NOT NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'KEY idx_dispatch_contracts_employee_period (employee_id, start_date, end_date),'
            . 'KEY idx_dispatch_contracts_company (dispatch_company_id),'
            . 'CONSTRAINT fk_dispatch_contracts_employee FOREIGN KEY (employee_id) '
            . 'REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT fk_dispatch_contracts_company FOREIGN KEY (dispatch_company_id) '
            . 'REFERENCES dispatch_companies (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT chk_dispatch_contracts_dates CHECK (start_date <= end_date)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE dispatch_contracts');
        $pdo->exec('DROP TABLE dispatch_companies');
    }
}
