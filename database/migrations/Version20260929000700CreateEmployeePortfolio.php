<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260929000700CreateEmployeePortfolio implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE skills ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'name VARCHAR(120) NOT NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_skills_name (name)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        $pdo->exec(
            'CREATE TABLE employee_skills ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'employee_id BIGINT UNSIGNED NOT NULL,'
            . 'skill_id BIGINT UNSIGNED NOT NULL,'
            . 'proficiency VARCHAR(20) NOT NULL,'
            . 'years_experience DECIMAL(4,1) NULL,'
            . 'notes TEXT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'archived_at DATETIME NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_employee_skills_employee_skill (employee_id, skill_id),'
            . 'KEY idx_employee_skills_employee_status (employee_id, status),'
            . 'KEY idx_employee_skills_skill (skill_id),'
            . 'CONSTRAINT fk_employee_skills_employee FOREIGN KEY (employee_id) '
            . 'REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT fk_employee_skills_skill FOREIGN KEY (skill_id) '
            . 'REFERENCES skills (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . "CONSTRAINT chk_employee_skills_proficiency CHECK (proficiency IN ('beginner', 'intermediate', 'advanced', 'expert')),"
            . 'CONSTRAINT chk_employee_skills_years CHECK (years_experience IS NULL OR (years_experience >= 0 AND years_experience <= 99.9)),'
            . "CONSTRAINT chk_employee_skills_status CHECK ((status = 'active' AND archived_at IS NULL) OR (status = 'archived' AND archived_at IS NOT NULL))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        $pdo->exec(
            'CREATE TABLE employee_projects ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'employee_id BIGINT UNSIGNED NOT NULL,'
            . 'project_name VARCHAR(160) NOT NULL,'
            . 'role VARCHAR(120) NOT NULL,'
            . 'start_date DATE NOT NULL,'
            . 'end_date DATE NULL,'
            . 'description TEXT NULL,'
            . 'responsibilities TEXT NULL,'
            . 'technologies TEXT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'archived_at DATETIME NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'KEY idx_employee_projects_employee_status (employee_id, status),'
            . 'KEY idx_employee_projects_employee_period (employee_id, start_date, end_date),'
            . 'CONSTRAINT fk_employee_projects_employee FOREIGN KEY (employee_id) '
            . 'REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT chk_employee_projects_dates CHECK (end_date IS NULL OR start_date <= end_date),'
            . "CONSTRAINT chk_employee_projects_status CHECK ((status = 'active' AND archived_at IS NULL) OR (status = 'archived' AND archived_at IS NOT NULL))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        $pdo->exec(
            'CREATE TABLE employee_certifications ('
            . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . 'employee_id BIGINT UNSIGNED NOT NULL,'
            . 'certification_name VARCHAR(160) NOT NULL,'
            . 'issuing_organization VARCHAR(120) NOT NULL,'
            . 'obtained_date DATE NOT NULL,'
            . 'expiration_date DATE NULL,'
            . 'credential_identifier VARCHAR(120) NULL,'
            . 'notes TEXT NULL,'
            . "status VARCHAR(20) NOT NULL DEFAULT 'active',"
            . 'archived_at DATETIME NULL,'
            . 'created_at DATETIME NOT NULL,'
            . 'updated_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (id),'
            . 'UNIQUE KEY uq_employee_certification_identity (employee_id, certification_name, issuing_organization, obtained_date),'
            . 'KEY idx_employee_certifications_employee_status (employee_id, status),'
            . 'KEY idx_employee_certifications_expiration (employee_id, expiration_date),'
            . 'CONSTRAINT fk_employee_certifications_employee FOREIGN KEY (employee_id) '
            . 'REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,'
            . 'CONSTRAINT chk_employee_certifications_dates CHECK (expiration_date IS NULL OR obtained_date <= expiration_date),'
            . "CONSTRAINT chk_employee_certifications_status CHECK ((status = 'active' AND archived_at IS NULL) OR (status = 'archived' AND archived_at IS NOT NULL))"
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE employee_certifications');
        $pdo->exec('DROP TABLE employee_projects');
        $pdo->exec('DROP TABLE employee_skills');
        $pdo->exec('DROP TABLE skills');
    }
}

