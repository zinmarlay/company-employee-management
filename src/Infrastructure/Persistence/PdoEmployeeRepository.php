<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\EmployeeInput;
use App\Database\LazyPdoConnection;
use App\Domain\Employee\EmployeeDuplicateException;
use App\Domain\Employee\EmployeeRepositoryInterface;
use PDO;
use PDOException;

final class PdoEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
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
        $statement = $this->connection->get()->prepare(
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
                'employee_code' => $input->employeeCode,
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

        return (int) $this->connection->get()->lastInsertId();
    }

    public function update(int $id, EmployeeInput $input, string $updatedAt): void
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE employees SET '
            . 'branch_id = :branch_id, department_id = :department_id, '
            . 'employee_code = :employee_code, first_name = :first_name, '
            . 'last_name = :last_name, first_name_kana = :first_name_kana, '
            . 'last_name_kana = :last_name_kana, email = :email, phone = :phone, '
            . 'position_title = :position_title, employee_type = :employee_type, '
            . 'hire_date = :hire_date, updated_at = :updated_at '
            . 'WHERE id = :id',
        );

        try {
            $statement->execute([
                'id' => $id,
                'branch_id' => $input->branchId,
                'department_id' => $input->departmentId,
                'employee_code' => $input->employeeCode,
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
