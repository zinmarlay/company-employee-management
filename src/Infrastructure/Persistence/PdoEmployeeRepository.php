<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\EmployeeInput;
use App\Application\DTO\EmployeeSearchCriteria;
use App\Application\DTO\EmployeeSearchResult;
use App\Database\LazyPdoConnection;
use App\Domain\Employee\EmployeeCodeAllocatorInterface;
use App\Domain\Employee\EmployeeDuplicateException;
use App\Domain\Employee\EmployeeRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

final class PdoEmployeeRepository implements EmployeeRepositoryInterface
{
    private readonly EmployeeCodeAllocatorInterface $codeAllocator;

    public function __construct(
        private readonly LazyPdoConnection $connection,
        ?EmployeeCodeAllocatorInterface $codeAllocator = null,
    ) {
        $this->codeAllocator = $codeAllocator ?? new PdoEmployeeCodeAllocator($connection);
    }

    public function listBasic(int $limit): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT e.id, e.employee_code, e.first_name, e.last_name, '
            . 'b.name AS branch_name, d.name AS department_name, '
            . 'e.position_title, e.employee_type, e.status '
            . 'FROM employees e '
            . 'INNER JOIN branches b ON b.id = e.branch_id '
            . 'LEFT JOIN departments d ON d.id = e.department_id AND d.branch_id = e.branch_id '
            . 'ORDER BY e.last_name ASC, e.first_name ASC, e.employee_code ASC, e.id ASC '
            . 'LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function search(EmployeeSearchCriteria $criteria): EmployeeSearchResult
    {
        [$where, $parameters] = $this->searchWhere($criteria);

        $countStatement = $this->connection->get()->prepare(
            'SELECT COUNT(*) FROM employees e '
            . 'INNER JOIN branches b ON b.id = e.branch_id '
            . 'LEFT JOIN departments d ON d.id = e.department_id AND d.branch_id = e.branch_id '
            . $where,
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $criteria->perPage));
        $page = min($criteria->page, $totalPages);
        $offset = ($page - 1) * $criteria->perPage;

        $sortExpressions = [
            'employee_code' => 'e.employee_code',
            'name' => 'CONCAT(e.last_name, \' \', e.first_name)',
            'branch' => 'b.name',
            'department' => 'COALESCE(d.name, \'\')',
            'employee_type' => 'e.employee_type',
            'status' => 'e.status',
        ];
        $sortExpression = $sortExpressions[$criteria->sort] ?? $sortExpressions['employee_code'];
        $direction = $criteria->direction === 'desc' ? 'DESC' : 'ASC';

        $statement = $this->connection->get()->prepare(
            'SELECT e.id, e.branch_id, e.department_id, e.employee_code, '
            . 'e.first_name, e.last_name, e.first_name_kana, e.last_name_kana, '
            . 'e.email, e.phone, e.position_title, e.employee_type, e.hire_date, '
            . 'e.status, e.created_at, e.updated_at, '
            . 'b.code AS branch_code, b.name AS branch_name, b.status AS branch_status, '
            . 'd.code AS department_code, d.name AS department_name, d.status AS department_status '
            . 'FROM employees e '
            . 'INNER JOIN branches b ON b.id = e.branch_id '
            . 'LEFT JOIN departments d ON d.id = e.department_id AND d.branch_id = e.branch_id '
            . $where
            . ' ORDER BY ' . $sortExpression . ' ' . $direction
            . ', e.employee_code ASC, e.id ASC LIMIT :limit OFFSET :offset',
        );
        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->bindValue('limit', $criteria->perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return new EmployeeSearchResult($statement->fetchAll(), $total, $page, $criteria->perPage);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT e.id, e.branch_id, e.department_id, e.employee_code, '
            . 'e.first_name, e.last_name, e.first_name_kana, e.last_name_kana, '
            . 'e.email, e.phone, e.position_title, e.employee_type, e.hire_date, '
            . 'e.status, e.created_at, e.updated_at, '
            . 'b.code AS branch_code, b.name AS branch_name, b.status AS branch_status, '
            . 'd.code AS department_code, d.name AS department_name, d.status AS department_status '
            . 'FROM employees e '
            . 'INNER JOIN branches b ON b.id = e.branch_id '
            . 'LEFT JOIN departments d ON d.id = e.department_id AND d.branch_id = e.branch_id '
            . 'WHERE e.id = :id',
        );
        $statement->execute(['id' => $id]);
        $employee = $statement->fetch();

        return $employee === false ? null : $employee;
    }

    public function employeeCodeExists(string $code, ?int $exceptId = null): bool
    {
        return $this->exists('employee_code', $code, $exceptId);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->exists('email', $email, $exceptId);
    }

    public function insert(EmployeeInput $input, string $createdAt, string $updatedAt): int
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $employeeCode = $this->codeAllocator->allocate();
            $statement = $pdo->prepare(
                'INSERT INTO employees '
                . '(branch_id, department_id, employee_code, first_name, last_name, '
                . 'first_name_kana, last_name_kana, email, phone, position_title, '
                . 'employee_type, hire_date, status, created_at, updated_at) '
                . 'VALUES (:branch_id, :department_id, :employee_code, :first_name, :last_name, '
                . ':first_name_kana, :last_name_kana, :email, :phone, :position_title, '
                . ':employee_type, :hire_date, :status, :created_at, :updated_at)',
            );

            try {
                $statement->execute([
                    'branch_id' => $input->branchId,
                    'department_id' => $input->departmentId,
                    'employee_code' => $employeeCode,
                    'first_name' => $input->firstName,
                    'last_name' => $input->lastName,
                    'first_name_kana' => $input->firstNameKana,
                    'last_name_kana' => $input->lastNameKana,
                    'email' => $input->email,
                    'phone' => $input->phone,
                    'position_title' => $input->positionTitle,
                    'employee_type' => $input->employeeType,
                    'hire_date' => $input->hireDate,
                    'status' => 'active',
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);
            } catch (PDOException $exception) {
                $this->throwKnownDuplicate($exception);
                throw $exception;
            }

            $id = (int) $pdo->lastInsertId();
            $pdo->commit();

            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(int $id, EmployeeInput $input, string $updatedAt): bool
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE employees SET '
            . 'branch_id = :branch_id, department_id = :department_id, '
            . 'first_name = :first_name, '
            . 'last_name = :last_name, first_name_kana = :first_name_kana, '
            . 'last_name_kana = :last_name_kana, email = :email, phone = :phone, '
            . 'position_title = :position_title, employee_type = :employee_type, '
            . 'hire_date = :hire_date, updated_at = :updated_at '
            . "WHERE id = :id AND status = 'active'",
        );

        try {
            $statement->execute([
                'id' => $id,
                'branch_id' => $input->branchId,
                'department_id' => $input->departmentId,
                'first_name' => $input->firstName,
                'last_name' => $input->lastName,
                'first_name_kana' => $input->firstNameKana,
                'last_name_kana' => $input->lastNameKana,
                'email' => $input->email,
                'phone' => $input->phone,
                'position_title' => $input->positionTitle,
                'employee_type' => $input->employeeType,
                'hire_date' => $input->hireDate,
                'updated_at' => $updatedAt,
            ]);
        } catch (PDOException $exception) {
            $this->throwKnownDuplicate($exception);
            throw $exception;
        }

        return $statement->rowCount() > 0;
    }

    public function deactivate(int $id, string $updatedAt): bool
    {
        $statement = $this->connection->get()->prepare(
            "UPDATE employees SET status = 'inactive', updated_at = :updated_at "
            . "WHERE id = :id AND status = 'active'",
        );
        $statement->execute([
            'id' => $id,
            'updated_at' => $updatedAt,
        ]);

        return $statement->rowCount() > 0;
    }

    private function exists(string $column, string $value, ?int $exceptId): bool
    {
        $sql = 'SELECT 1 FROM employees WHERE ' . $column . ' = :value';
        $parameters = ['value' => $value];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->get()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function searchWhere(EmployeeSearchCriteria $criteria): array
    {
        $conditions = [];
        $parameters = [];

        if ($criteria->keyword !== null) {
            $keyword = '%' . $this->escapeLike($criteria->keyword) . '%';
            $conditions[] = '('
                . 'e.employee_code LIKE :keyword_code ESCAPE \'\\\\\' '
                . 'OR e.first_name LIKE :keyword_first_name ESCAPE \'\\\\\' '
                . 'OR e.last_name LIKE :keyword_last_name ESCAPE \'\\\\\' '
                . 'OR e.first_name_kana LIKE :keyword_first_name_kana ESCAPE \'\\\\\' '
                . 'OR e.last_name_kana LIKE :keyword_last_name_kana ESCAPE \'\\\\\' '
                . 'OR e.email LIKE :keyword_email ESCAPE \'\\\\\''
                . ')';
            $parameters = [
                'keyword_code' => $keyword,
                'keyword_first_name' => $keyword,
                'keyword_last_name' => $keyword,
                'keyword_first_name_kana' => $keyword,
                'keyword_last_name_kana' => $keyword,
                'keyword_email' => $keyword,
            ];
        }

        if ($criteria->branchId !== null) {
            $conditions[] = 'e.branch_id = :branch_id';
            $parameters['branch_id'] = $criteria->branchId;
        }
        if ($criteria->departmentId !== null) {
            $conditions[] = 'e.department_id = :department_id';
            $parameters['department_id'] = $criteria->departmentId;
        }
        if ($criteria->employeeType !== null) {
            $conditions[] = 'e.employee_type = :employee_type';
            $parameters['employee_type'] = $criteria->employeeType;
        }
        if ($criteria->status !== null) {
            $conditions[] = 'e.status = :status';
            $parameters['status'] = $criteria->status;
        }

        return [
            $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function throwKnownDuplicate(PDOException $exception): void
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        if ($sqlState !== '23000' && !str_contains($message, 'duplicate')) {
            return;
        }

        if (str_contains($message, 'uq_employees_employee_code')
            || str_contains($message, 'employee_code')
        ) {
            throw new EmployeeDuplicateException('employee_code', (int) $exception->getCode(), $exception);
        }

        if (str_contains($message, 'uq_employees_email')
            || str_contains($message, 'email')
        ) {
            throw new EmployeeDuplicateException('email', (int) $exception->getCode(), $exception);
        }
    }
}
