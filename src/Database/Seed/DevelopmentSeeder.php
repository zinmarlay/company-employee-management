<?php

declare(strict_types=1);

namespace App\Database\Seed;

use App\Application\Support\Clock;
use App\Domain\Organization\PrefectureCatalog;
use App\Domain\Organization\DepartmentCatalog;
use PDO;
use Throwable;

final class DevelopmentSeeder
{
    private const COMPANY_CODE = 'SAMPLE-COMPANY';

    /** @var array<string, int> */
    private array $branchIds = [];

    /** @var array<string, int> */
    private array $departmentIds = [];

    /** @var array<string, int> */
    private array $employeeIds = [];

    /** @var array<string, int> */
    private array $dispatchCompanyIds = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly ?PrefectureCatalog $prefectures = null,
        private readonly ?DepartmentCatalog $departmentCatalog = null,
    ) {
    }

    /**
     * @return array{companies: int, branches: int, departments: int, employees: int, dispatch_companies: int, dispatch_contracts: int}
     */
    public function seed(): array
    {
        if ($this->pdo->inTransaction()) {
            throw new DevelopmentSeedException('Development seeding requires a PDO connection without an active transaction.');
        }

        $now = $this->clock->nowUtc();
        $timestamp = $now->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            $companyId = $this->upsertCompany($timestamp);
            $this->upsertBranches($companyId, $timestamp);
            $this->upsertDepartments($timestamp);
            $this->upsertEmployees($timestamp);
            $this->synchronizeEmployeeCodeSequence();
            $this->upsertPortfolio($timestamp);
            $this->upsertDispatchCompanies($timestamp);
            $this->upsertContracts($timestamp);

            $this->pdo->commit();

            return [
                'companies' => 1,
                'branches' => count($this->branchIds),
                'departments' => count($this->departmentIds),
                'employees' => count($this->employeeIds),
                'dispatch_companies' => count($this->dispatchCompanyIds),
                'dispatch_contracts' => 6,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof DevelopmentSeedException) {
                throw $exception;
            }

            throw new DevelopmentSeedException(
                'Development seed failed and was rolled back: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    private function upsertCompany(string $timestamp): int
    {
        $existing = $this->fetchOne(
            'SELECT id, code, name FROM companies WHERE code = :code',
            ['code' => self::COMPANY_CODE],
        );

        if ($existing !== null) {
            $this->assertSame($existing, [
                'code' => self::COMPANY_CODE,
                'name' => 'サンプル株式会社',
            ], 'company');

            $this->execute(
                'UPDATE companies SET name = :name, updated_at = :updated_at WHERE id = :id',
                ['name' => 'サンプル株式会社', 'updated_at' => $timestamp, 'id' => $existing['id']],
            );

            return (int) $existing['id'];
        }

        $this->execute(
            'INSERT INTO companies (code, name, created_at, updated_at) '
            . 'VALUES (:code, :name, :created_at, :updated_at)',
            [
                'code' => self::COMPANY_CODE,
                'name' => 'サンプル株式会社',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return (int) $this->pdo->lastInsertId();
    }

    private function upsertBranches(int $companyId, string $timestamp): void
    {
        $branches = [
            'TOKYO' => ['東京都', '東京都千代田区丸の内1-1-1', '03-1234-5678'],
            'OSAKA' => ['大阪府', '大阪府大阪市北区梅田1-1-1', '06-1234-5678'],
        ];

        $catalog = $this->prefectures ?? new PrefectureCatalog();
        foreach ($branches as $code => [$city, $address, $phone]) {
            $prefecture = $catalog->find($code);
            if ($prefecture === null) {
                throw new DevelopmentSeedException('Seed branch code is not present in the prefecture catalog: ' . $code);
            }
            $name = $prefecture['branch_name'];
            $existing = $this->fetchOne(
                'SELECT id, code, name, city, address, phone, status FROM branches '
                . 'WHERE company_id = :company_id AND code = :code',
                ['company_id' => $companyId, 'code' => $code],
            );
            $expected = [
                'code' => $code,
                'name' => $name,
                'city' => $city,
                'address' => $address,
                'phone' => $phone,
                'status' => 'active',
            ];

            if ($existing !== null) {
                $this->assertSame($existing, $expected, 'branch ' . $code);
                $this->execute(
                    'UPDATE branches SET name = :name, city = :city, address = :address, phone = :phone, '
                    . 'status = :status, updated_at = :updated_at WHERE id = :id',
                    [
                        'name' => $name,
                        'city' => $city,
                        'address' => $address,
                        'phone' => $phone,
                        'status' => 'active',
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                $this->branchIds[$code] = (int) $existing['id'];
                continue;
            }

            $this->execute(
                'INSERT INTO branches (company_id, code, name, city, address, phone, status, created_at, updated_at) '
                . 'VALUES (:company_id, :code, :name, :city, :address, :phone, :status, :created_at, :updated_at)',
                [
                    'company_id' => $companyId,
                    ...$expected,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
            $this->branchIds[$code] = (int) $this->pdo->lastInsertId();
        }
    }

    private function upsertDepartments(string $timestamp): void
    {
        $departments = [
            'TOKYO:DEV' => [$this->branchIds['TOKYO'], 'DEV'],
            'TOKYO:SALES' => [$this->branchIds['TOKYO'], 'SALES'],
            'OSAKA:DEV' => [$this->branchIds['OSAKA'], 'DEV'],
        ];

        $catalog = $this->departmentCatalog ?? new DepartmentCatalog();
        foreach ($departments as $key => [$branchId, $code]) {
            $department = $catalog->find($code);
            if ($department === null) {
                throw new DevelopmentSeedException('Seed department code is not present in the department catalog: ' . $code);
            }
            $name = $department['name'];
            $existing = $this->fetchOne(
                'SELECT id, code, name, status FROM departments '
                . 'WHERE branch_id = :branch_id AND code = :code',
                ['branch_id' => $branchId, 'code' => $code],
            );
            $expected = ['code' => $code, 'name' => $name, 'status' => 'active'];

            if ($existing !== null) {
                $this->assertSame($existing, $expected, 'department ' . $key);
                $this->execute(
                    'UPDATE departments SET name = :name, status = :status, updated_at = :updated_at WHERE id = :id',
                    [
                        'name' => $name,
                        'status' => 'active',
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                $this->departmentIds[$key] = (int) $existing['id'];
                continue;
            }

            $this->execute(
                'INSERT INTO departments (branch_id, code, name, status, created_at, updated_at) '
                . 'VALUES (:branch_id, :code, :name, :status, :created_at, :updated_at)',
                [
                    'branch_id' => $branchId,
                    ...$expected,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
            $this->departmentIds[$key] = (int) $this->pdo->lastInsertId();
        }
    }

    private function upsertEmployees(string $timestamp): void
    {
        $employees = [
            [
                'code' => 'EMP000001', 'first_name' => '太郎', 'last_name' => '山田',
                'first_name_kana' => 'タロウ', 'last_name_kana' => 'ヤマダ',
                'email' => 'taro.yamada@example.test', 'phone' => '03-1111-0001',
                'position_title' => 'シニアエンジニア', 'type' => 'permanent',
                'hire_date' => '2022-04-01', 'department' => 'TOKYO:DEV',
            ],
            [
                'code' => 'EMP000002', 'first_name' => '花子', 'last_name' => '佐藤',
                'first_name_kana' => 'ハナコ', 'last_name_kana' => 'サトウ',
                'email' => 'hanako.sato@example.test', 'phone' => '03-1111-0002',
                'position_title' => 'Webエンジニア', 'type' => 'dispatched',
                'hire_date' => '2025-04-01', 'department' => 'TOKYO:DEV',
            ],
            [
                'code' => 'EMP000003', 'first_name' => '一郎', 'last_name' => '鈴木',
                'first_name_kana' => 'イチロウ', 'last_name_kana' => 'スズキ',
                'email' => 'ichiro.suzuki@example.test', 'phone' => '03-1111-0003',
                'position_title' => '営業担当', 'type' => 'permanent',
                'hire_date' => '2023-10-01', 'department' => 'TOKYO:SALES',
            ],
            [
                'code' => 'EMP000004', 'first_name' => '美咲', 'last_name' => '高橋',
                'first_name_kana' => 'ミサキ', 'last_name_kana' => 'タカハシ',
                'email' => 'misaki.takahashi@example.test', 'phone' => '06-1111-0004',
                'position_title' => 'PHPエンジニア', 'type' => 'dispatched',
                'hire_date' => '2025-07-01', 'department' => 'OSAKA:DEV',
            ],
        ];

        foreach ($employees as $employee) {
            $existingByCode = $this->fetchOne(
                'SELECT id, branch_id, department_id, employee_code, first_name, last_name, first_name_kana, '
                . 'last_name_kana, email, phone, position_title, employee_type, hire_date, status '
                . 'FROM employees WHERE employee_code = :employee_code',
                ['employee_code' => $employee['code']],
            );
            $existingByEmail = $this->fetchOne(
                'SELECT id, branch_id, department_id, employee_code, first_name, last_name, first_name_kana, '
                . 'last_name_kana, email, phone, position_title, employee_type, hire_date, status '
                . 'FROM employees WHERE email = :email',
                ['email' => $employee['email']],
            );

            if ($existingByCode !== null && $existingByEmail !== null && $existingByCode['id'] !== $existingByEmail['id']) {
                throw new DevelopmentSeedException(sprintf(
                    'Employee seed identifier conflict for %s: code and email belong to different records.',
                    $employee['code'],
                ));
            }

            $existing = $existingByCode ?? $existingByEmail;
            if ($existing !== null && ($existing['employee_code'] !== $employee['code'] || $existing['email'] !== $employee['email'])) {
                throw new DevelopmentSeedException(sprintf(
                    'Employee seed identifier conflict for %s.',
                    $employee['code'],
                ));
            }

            $values = [
                'branch_id' => $this->branchIds[explode(':', $employee['department'], 2)[0]],
                'department_id' => $this->departmentIds[$employee['department']],
                'employee_code' => $employee['code'],
                'first_name' => $employee['first_name'],
                'last_name' => $employee['last_name'],
                'first_name_kana' => $employee['first_name_kana'],
                'last_name_kana' => $employee['last_name_kana'],
                'email' => $employee['email'],
                'phone' => $employee['phone'],
                'position_title' => $employee['position_title'],
                'employee_type' => $employee['type'],
                'hire_date' => $employee['hire_date'],
                'status' => 'active',
            ];

            if ($existing !== null) {
                $this->assertEmployeeMatches($existing, $values, $employee['code']);
                $this->execute(
                    'UPDATE employees SET branch_id = :branch_id, department_id = :department_id, '
                    . 'first_name = :first_name, last_name = :last_name, first_name_kana = :first_name_kana, '
                    . 'last_name_kana = :last_name_kana, phone = :phone, position_title = :position_title, '
                    . 'employee_type = :employee_type, hire_date = :hire_date, status = :status, '
                    . 'updated_at = :updated_at WHERE id = :id',
                    [
                        'branch_id' => $values['branch_id'],
                        'department_id' => $values['department_id'],
                        'first_name' => $values['first_name'],
                        'last_name' => $values['last_name'],
                        'first_name_kana' => $values['first_name_kana'],
                        'last_name_kana' => $values['last_name_kana'],
                        'phone' => $values['phone'],
                        'position_title' => $values['position_title'],
                        'employee_type' => $values['employee_type'],
                        'hire_date' => $values['hire_date'],
                        'status' => $values['status'],
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                $this->employeeIds[$employee['code']] = (int) $existing['id'];
                continue;
            }

            $this->execute(
                'INSERT INTO employees '
                . '(branch_id, department_id, employee_code, first_name, last_name, first_name_kana, last_name_kana, '
                . 'email, phone, position_title, employee_type, hire_date, status, created_at, updated_at) '
                . 'VALUES (:branch_id, :department_id, :employee_code, :first_name, :last_name, :first_name_kana, '
                . ':last_name_kana, :email, :phone, :position_title, :employee_type, :hire_date, :status, '
                . ':created_at, :updated_at)',
                [...$values, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            );
            $this->employeeIds[$employee['code']] = (int) $this->pdo->lastInsertId();
        }
    }

    private function upsertPortfolio(string $timestamp): void
    {
        $skillIds = $this->upsertSkills($timestamp);
        $this->upsertEmployeeSkills($skillIds, $timestamp);
        $this->upsertProjects($timestamp);
        $this->upsertCertifications($timestamp);
    }

    /** @return array<string, int> */
    private function upsertSkills(string $timestamp): array
    {
        $skillIds = [];

        foreach (['PHP', 'Laravel', 'JavaScript', 'MySQL', 'React', 'AWS', 'Git'] as $name) {
            $existing = $this->fetchOne(
                'SELECT id, name FROM skills WHERE name = :name',
                ['name' => $name],
            );

            if ($existing !== null) {
                $this->assertSame($existing, ['name' => $name], 'skill ' . $name);
                $this->execute(
                    'UPDATE skills SET updated_at = :updated_at WHERE id = :id',
                    ['updated_at' => $timestamp, 'id' => $existing['id']],
                );
                $skillIds[$name] = (int) $existing['id'];
                continue;
            }

            $this->execute(
                'INSERT INTO skills (name, created_at, updated_at) '
                . 'VALUES (:name, :created_at, :updated_at)',
                ['name' => $name, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            );
            $skillIds[$name] = (int) $this->pdo->lastInsertId();
        }

        return $skillIds;
    }

    /** @param array<string, int> $skillIds */
    private function upsertEmployeeSkills(array $skillIds, string $timestamp): void
    {
        $assignments = [
            ['EMP000001', 'PHP', 'advanced', 5.0, 'Backend development and service maintenance.'],
            ['EMP000001', 'Laravel', 'advanced', 4.0, 'Application development with Laravel.'],
            ['EMP000001', 'JavaScript', 'intermediate', 3.0, 'Browser-based administration features.'],
            ['EMP000001', 'MySQL', 'advanced', 4.0, 'Schema design and query optimization.'],
            ['EMP000001', 'Git', 'advanced', 5.0, 'Branching and code review workflows.'],
            ['EMP000002', 'PHP', 'intermediate', 2.0, 'Web application feature development.'],
            ['EMP000002', 'JavaScript', 'advanced', 4.0, 'Interactive business screens and validation.'],
            ['EMP000002', 'React', 'advanced', 3.0, 'Reusable front-end components.'],
            ['EMP000002', 'Git', 'intermediate', 3.0, 'Team collaboration and pull requests.'],
            ['EMP000003', 'JavaScript', 'beginner', 1.5, 'Sales dashboard configuration and reporting.'],
            ['EMP000003', 'Git', 'beginner', 1.0, 'Documentation and dashboard change tracking.'],
            ['EMP000004', 'PHP', 'advanced', 6.0, 'Legacy application integration and support.'],
            ['EMP000004', 'MySQL', 'advanced', 5.0, 'Production data migration and verification.'],
            ['EMP000004', 'AWS', 'intermediate', 2.0, 'Cloud deployment and operational monitoring.'],
            ['EMP000004', 'Git', 'advanced', 6.0, 'Release management across environments.'],
        ];

        foreach ($assignments as [$employeeCode, $skillName, $proficiency, $years, $notes]) {
            $employeeId = $this->employeeIds[$employeeCode];
            $skillId = $skillIds[$skillName];
            $existing = $this->fetchOne(
                'SELECT id FROM employee_skills WHERE employee_id = :employee_id AND skill_id = :skill_id',
                ['employee_id' => $employeeId, 'skill_id' => $skillId],
            );
            $values = [
                'employee_id' => $employeeId,
                'skill_id' => $skillId,
                'proficiency' => $proficiency,
                'years_experience' => $years,
                    'notes' => $notes,
                    'status' => 'active',
                    'archived_at' => null,
                    'updated_at' => $timestamp,
            ];

            if ($existing !== null) {
                $this->execute(
                    'UPDATE employee_skills SET proficiency = :proficiency, years_experience = :years_experience, '
                    . 'notes = :notes, status = :status, archived_at = :archived_at, updated_at = :updated_at '
                    . 'WHERE id = :id',
                    [
                        'proficiency' => $proficiency,
                        'years_experience' => $years,
                        'notes' => $notes,
                        'status' => 'active',
                        'archived_at' => null,
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                continue;
            }

            $this->execute(
                'INSERT INTO employee_skills '
                . '(employee_id, skill_id, proficiency, years_experience, notes, status, archived_at, created_at, updated_at) '
                . 'VALUES (:employee_id, :skill_id, :proficiency, :years_experience, :notes, :status, :archived_at, :created_at, :updated_at)',
                [...$values, 'created_at' => $timestamp],
            );
        }
    }

    private function upsertProjects(string $timestamp): void
    {
        $projects = [
            [
                'EMP000001', 'Employee Management System', 'Backend Developer', '2023-04-01', '2024-03-31',
                'Modernized the internal employee directory and portfolio workflow.',
                'Designed service boundaries, implemented employee APIs, and improved database query performance.',
                'PHP, MySQL, JavaScript',
            ],
            [
                'EMP000002', 'Internal Sales Management System', 'Full Stack Developer', '2025-04-15', null,
                'Developed a shared sales pipeline and customer activity workspace for the Tokyo sales team.',
                'Built reusable React screens, implemented Laravel endpoints, and coordinated user acceptance testing.',
                'Laravel, React, MySQL',
            ],
            [
                'EMP000003', 'Customer Sales Dashboard', 'Business Systems Coordinator', '2024-04-01', '2025-02-28',
                'Created a reporting dashboard that gives sales staff a consistent view of monthly activity.',
                'Defined reporting requirements, verified imported data, and documented dashboard operations.',
                'JavaScript, Git',
            ],
            [
                'EMP000004', 'Cloud Migration Project', 'Application Engineer', '2025-07-01', '2026-02-28',
                'Migrated a PHP and MySQL workload to a monitored AWS environment with a repeatable release process.',
                'Prepared migration runbooks, validated production data, and supported cutover and post-release monitoring.',
                'AWS, PHP, MySQL',
            ],
        ];

        foreach ($projects as [$employeeCode, $projectName, $role, $startDate, $endDate, $description, $responsibilities, $technologies]) {
            $employeeId = $this->employeeIds[$employeeCode];
            $existing = $this->fetchOne(
                'SELECT id FROM employee_projects WHERE employee_id = :employee_id '
                . 'AND project_name = :project_name AND start_date = :start_date LIMIT 1',
                [
                    'employee_id' => $employeeId,
                    'project_name' => $projectName,
                    'start_date' => $startDate,
                ],
            );
            $values = [
                'employee_id' => $employeeId,
                'project_name' => $projectName,
                'role' => $role,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $description,
                'responsibilities' => $responsibilities,
                'technologies' => $technologies,
                'status' => 'active',
                'archived_at' => null,
                'updated_at' => $timestamp,
            ];

            if ($existing !== null) {
                $this->execute(
                    'UPDATE employee_projects SET role = :role, end_date = :end_date, description = :description, '
                    . 'responsibilities = :responsibilities, technologies = :technologies, status = :status, '
                    . 'archived_at = :archived_at, updated_at = :updated_at WHERE id = :id',
                    [
                        'role' => $role,
                        'end_date' => $endDate,
                        'description' => $description,
                        'responsibilities' => $responsibilities,
                        'technologies' => $technologies,
                        'status' => 'active',
                        'archived_at' => null,
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                continue;
            }

            $this->execute(
                'INSERT INTO employee_projects '
                . '(employee_id, project_name, role, start_date, end_date, description, responsibilities, technologies, status, archived_at, created_at, updated_at) '
                . 'VALUES (:employee_id, :project_name, :role, :start_date, :end_date, :description, :responsibilities, :technologies, :status, :archived_at, :created_at, :updated_at)',
                [...$values, 'created_at' => $timestamp],
            );
        }
    }

    private function upsertCertifications(string $timestamp): void
    {
        $certifications = [
            ['EMP000001', 'AWS Certified Solutions Architect', 'Amazon Web Services', '2024-06-15', '2027-06-15', 'SAA-C03-EMP000001', 'Cloud architecture certification for internal platform work.'],
            ['EMP000001', 'Zend Certified PHP Engineer', 'Zend by Perforce', '2023-11-10', null, 'ZCE-PHP-EMP000001', 'PHP application development certification.'],
            ['EMP000002', 'AWS Certified Developer', 'Amazon Web Services', '2025-03-20', '2028-03-20', 'DVA-C02-EMP000002', 'Supports the ongoing sales platform delivery work.'],
            ['EMP000003', 'IT Passport Examination', 'Information-technology Promotion Agency, Japan', '2024-02-15', null, 'IP-2024-EMP000003', 'Business and information technology fundamentals.'],
            ['EMP000004', 'AWS Certified Solutions Architect', 'Amazon Web Services', '2025-08-20', '2028-08-20', 'SAA-C03-EMP000004', 'Cloud migration and infrastructure design certification.'],
        ];

        foreach ($certifications as [$employeeCode, $name, $issuer, $obtainedDate, $expirationDate, $credentialIdentifier, $notes]) {
            $employeeId = $this->employeeIds[$employeeCode];
            $existing = $this->fetchOne(
                'SELECT id FROM employee_certifications WHERE employee_id = :employee_id '
                . 'AND certification_name = :certification_name AND issuing_organization = :issuing_organization '
                . 'AND obtained_date = :obtained_date LIMIT 1',
                [
                    'employee_id' => $employeeId,
                    'certification_name' => $name,
                    'issuing_organization' => $issuer,
                    'obtained_date' => $obtainedDate,
                ],
            );
            $values = [
                'employee_id' => $employeeId,
                'certification_name' => $name,
                'issuing_organization' => $issuer,
                'obtained_date' => $obtainedDate,
                'expiration_date' => $expirationDate,
                'credential_identifier' => $credentialIdentifier,
                'notes' => $notes,
                'status' => 'active',
                'archived_at' => null,
                'updated_at' => $timestamp,
            ];

            if ($existing !== null) {
                $this->execute(
                    'UPDATE employee_certifications SET expiration_date = :expiration_date, '
                    . 'credential_identifier = :credential_identifier, notes = :notes, status = :status, '
                    . 'archived_at = :archived_at, updated_at = :updated_at WHERE id = :id',
                    [
                        'expiration_date' => $expirationDate,
                        'credential_identifier' => $credentialIdentifier,
                        'notes' => $notes,
                        'status' => 'active',
                        'archived_at' => null,
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                continue;
            }

            $this->execute(
                'INSERT INTO employee_certifications '
                . '(employee_id, certification_name, issuing_organization, obtained_date, expiration_date, credential_identifier, notes, status, archived_at, created_at, updated_at) '
                . 'VALUES (:employee_id, :certification_name, :issuing_organization, :obtained_date, :expiration_date, :credential_identifier, :notes, :status, :archived_at, :created_at, :updated_at)',
                [...$values, 'created_at' => $timestamp],
            );
        }
    }

    private function upsertDispatchCompanies(string $timestamp): void
    {
        $companies = [
            'TECH-PARTNERS' => ['テックパートナーズ株式会社', '03-9876-1000', 'contact@tech-partners.example.test', '東京都新宿区西新宿1-1-1'],
            'NEXT-STAFF' => ['ネクストスタッフ株式会社', '06-9876-2000', 'contact@next-staff.example.test', '大阪府大阪市北区梅田2-2-2'],
        ];

        foreach ($companies as $code => [$name, $phone, $email, $address]) {
            $existing = $this->fetchOne(
                'SELECT id, code, name, phone, email, address, status FROM dispatch_companies WHERE code = :code',
                ['code' => $code],
            );
            $expected = compact('code', 'name', 'phone', 'email', 'address') + ['status' => 'active'];

            if ($existing !== null) {
                $this->assertSame($existing, $expected, 'dispatch company ' . $code);
                $this->execute(
                    'UPDATE dispatch_companies SET name = :name, phone = :phone, email = :email, address = :address, '
                    . 'status = :status, updated_at = :updated_at WHERE id = :id',
                    [
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'address' => $address,
                        'status' => 'active',
                        'updated_at' => $timestamp,
                        'id' => $existing['id'],
                    ],
                );
                $this->dispatchCompanyIds[$code] = (int) $existing['id'];
                continue;
            }

            $this->execute(
                'INSERT INTO dispatch_companies (code, name, phone, email, address, status, created_at, updated_at) '
                . 'VALUES (:code, :name, :phone, :email, :address, :status, :created_at, :updated_at)',
                [...$expected, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            );
            $this->dispatchCompanyIds[$code] = (int) $this->pdo->lastInsertId();
        }
    }

    private function upsertContracts(string $timestamp): void
    {
        // Keep these sample periods canonical. Their stable natural tuple is the
        // seed identity, so a later run cannot shift or overlap prior seed data.
        $contracts = [
            ['EMP000002', 'TECH-PARTNERS', '2026-03-29', '2026-05-27'],
            ['EMP000002', 'TECH-PARTNERS', '2026-05-28', '2026-10-02'],
            ['EMP000002', 'TECH-PARTNERS', '2026-10-03', '2026-11-09'],
            ['EMP000004', 'NEXT-STAFF', '2026-06-27', '2026-08-25'],
            ['EMP000004', 'NEXT-STAFF', '2026-08-26', '2026-10-25'],
            ['EMP000004', 'NEXT-STAFF', '2026-10-26', '2027-01-23'],
        ];

        foreach ($contracts as [$employeeCode, $companyCode, $startDate, $endDate]) {
            $employeeId = $this->employeeIds[$employeeCode];
            $companyId = $this->dispatchCompanyIds[$companyCode];

            $existing = $this->fetchOne(
                'SELECT id FROM dispatch_contracts WHERE employee_id = :employee_id '
                . 'AND dispatch_company_id = :dispatch_company_id AND start_date = :start_date AND end_date = :end_date',
                [
                    'employee_id' => $employeeId,
                    'dispatch_company_id' => $companyId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            );

            if ($existing !== null) {
                continue;
            }

            $overlap = $this->fetchOne(
                'SELECT id, start_date, end_date FROM dispatch_contracts WHERE employee_id = :employee_id '
                . 'AND start_date <= :end_date AND end_date >= :start_date LIMIT 1',
                ['employee_id' => $employeeId, 'start_date' => $startDate, 'end_date' => $endDate],
            );
            if ($overlap !== null) {
                throw new DevelopmentSeedException(sprintf(
                    'Contract seed period %s to %s overlaps existing contract #%s (%s to %s) for %s.',
                    $startDate,
                    $endDate,
                    $overlap['id'],
                    $overlap['start_date'],
                    $overlap['end_date'],
                    $employeeCode,
                ));
            }

            $this->execute(
                'INSERT INTO dispatch_contracts '
                . '(employee_id, dispatch_company_id, start_date, end_date, created_at, updated_at) '
                . 'VALUES (:employee_id, :dispatch_company_id, :start_date, :end_date, :created_at, :updated_at)',
                [
                    'employee_id' => $employeeId,
                    'dispatch_company_id' => $companyId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
        }
    }

    private function synchronizeEmployeeCodeSequence(): void
    {
        $sequence = $this->fetchOne(
            'SELECT next_value FROM employee_code_sequences WHERE sequence_name = :sequence_name',
            ['sequence_name' => 'employee_code'],
        );

        if ($sequence === null) {
            throw new DevelopmentSeedException('Employee-code sequence state is missing.');
        }

        if ((int) $sequence['next_value'] >= 5) {
            return;
        }

        $this->execute(
            'UPDATE employee_code_sequences SET next_value = :next_value '
            . 'WHERE sequence_name = :sequence_name',
            ['next_value' => 5, 'sequence_name' => 'employee_code'],
        );
    }

    /** @param array<string, mixed> $parameters */
    private function execute(string $sql, array $parameters): void
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
    }

    /** @param array<string, mixed> $parameters @return array<string, mixed>|null */
    private function fetchOne(string $sql, array $parameters): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $actual @param array<string, scalar|null> $expected */
    private function assertSame(array $actual, array $expected, string $record): void
    {
        foreach ($expected as $field => $value) {
            if (($actual[$field] ?? null) !== $value) {
                throw new DevelopmentSeedException(sprintf(
                    'Existing %s #%s has incompatible %s; expected %s, found %s.',
                    $record,
                    $actual['id'] ?? 'unknown',
                    $field,
                    var_export($value, true),
                    var_export($actual[$field] ?? null, true),
                ));
            }
        }
    }

    /** @param array<string, mixed> $actual @param array<string, mixed> $expected */
    private function assertEmployeeMatches(array $actual, array $expected, string $employeeCode): void
    {
        foreach ($expected as $field => $value) {
            $matches = in_array($field, ['branch_id', 'department_id'], true)
                ? (int) ($actual[$field] ?? 0) === (int) $value
                : (string) ($actual[$field] ?? '') === (string) $value;

            if (!$matches) {
                throw new DevelopmentSeedException(sprintf(
                    'Existing employee %s has incompatible %s; expected %s, found %s.',
                    $employeeCode,
                    $field,
                    var_export($value, true),
                    var_export($actual[$field] ?? null, true),
                ));
            }
        }
    }
}
