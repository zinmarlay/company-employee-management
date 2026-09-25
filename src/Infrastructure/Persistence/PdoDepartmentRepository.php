<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\DepartmentInput;
use App\Database\LazyPdoConnection;
use App\Domain\Organization\DepartmentDuplicateException;
use App\Domain\Organization\DepartmentRepositoryInterface;
use PDOException;

final class PdoDepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listManagement(int $limit): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT d.id, d.branch_id, d.code, d.name, d.description, d.status, d.created_at, d.updated_at, '
            . 'b.code AS branch_code, b.name AS branch_name, b.status AS branch_status, '
            . 'c.code AS company_code, c.name AS company_name, '
            . '(SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.branch_id = d.branch_id) AS employee_count '
            . 'FROM departments d INNER JOIN branches b ON b.id = d.branch_id '
            . 'INNER JOIN companies c ON c.id = b.company_id '
            . 'ORDER BY c.name ASC, b.name ASC, d.name ASC, d.code ASC, d.id ASC LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function listActive(): array
    {
        $statement = $this->connection->get()->query(
            "SELECT id, branch_id, code, name, status FROM departments WHERE status = 'active' ORDER BY branch_id ASC, name ASC, id ASC",
        );

        return $statement->fetchAll();
    }

    public function listByBranchId(int $branchId): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT d.id, d.branch_id, d.code, d.name, d.description, d.status, '
            . '(SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.branch_id = d.branch_id) AS employee_count '
            . 'FROM departments d WHERE d.branch_id = :branch_id ORDER BY d.name ASC, d.code ASC, d.id ASC',
        );
        $statement->execute(['branch_id' => $branchId]);

        return $statement->fetchAll();
    }

    public function listEmployeesByDepartment(int $departmentId): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT e.id, e.employee_code, e.first_name, e.last_name, e.employee_type, e.status '
            . 'FROM employees e WHERE e.department_id = :department_id ORDER BY e.last_name ASC, e.first_name ASC, e.employee_code ASC, e.id ASC',
        );
        $statement->execute(['department_id' => $departmentId]);

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT d.id, d.branch_id, d.code, d.name, d.description, d.status, d.created_at, d.updated_at, '
            . 'b.code AS branch_code, b.name AS branch_name, b.status AS branch_status, '
            . 'c.id AS company_id, c.code AS company_code, c.name AS company_name, '
            . '(SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.branch_id = d.branch_id) AS employee_count '
            . 'FROM departments d INNER JOIN branches b ON b.id = d.branch_id '
            . 'INNER JOIN companies c ON c.id = b.company_id WHERE d.id = :id',
        );
        $statement->execute(['id' => $id]);
        $department = $statement->fetch();

        return $department === false ? null : $department;
    }

    public function codeExists(int $branchId, string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM departments WHERE branch_id = :branch_id AND code = :code';
        $parameters = ['branch_id' => $branchId, 'code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }
        $statement = $this->connection->get()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function insert(DepartmentInput $input, string $createdAt, string $updatedAt): int
    {
        $statement = $this->connection->get()->prepare(
            'INSERT INTO departments (branch_id, code, name, description, status, created_at, updated_at) '
            . 'VALUES (:branch_id, :code, :name, :description, \'active\', :created_at, :updated_at)',
        );

        try {
            $statement->execute([
                'branch_id' => $input->branchId,
                'code' => $input->code,
                'name' => $input->name,
                'description' => $input->description,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        } catch (PDOException $exception) {
            $this->throwKnownDuplicate($exception);
            throw $exception;
        }

        return (int) $this->connection->get()->lastInsertId();
    }

    public function update(int $id, DepartmentInput $input, string $updatedAt): void
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE departments SET code = :code, name = :name, description = :description, updated_at = :updated_at WHERE id = :id',
        );

        try {
            $statement->execute([
                'id' => $id,
                'code' => $input->code,
                'name' => $input->name,
                'description' => $input->description,
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
            "UPDATE departments SET status = 'inactive', updated_at = :updated_at WHERE id = :id AND status = 'active'",
        );
        $statement->execute(['id' => $id, 'updated_at' => $updatedAt]);

        return $statement->rowCount() > 0;
    }

    private function throwKnownDuplicate(PDOException $exception): void
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        if ($sqlState === '23000' && (str_contains($message, 'uq_departments_branch_code') || str_contains($message, 'code'))) {
            throw new DepartmentDuplicateException((int) $exception->getCode(), $exception);
        }
    }
}
