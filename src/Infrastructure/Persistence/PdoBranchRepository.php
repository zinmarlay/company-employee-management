<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\BranchInput;
use App\Database\LazyPdoConnection;
use App\Domain\Organization\BranchDuplicateException;
use App\Domain\Organization\BranchRepositoryInterface;
use PDO;
use PDOException;

final class PdoBranchRepository implements BranchRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listManagement(int $limit): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT b.id, b.company_id, b.code, b.name, b.city, b.address, b.phone, b.status, '
            . 'b.created_at, b.updated_at, c.code AS company_code, c.name AS company_name, '
            . '(SELECT COUNT(*) FROM departments d WHERE d.branch_id = b.id) AS department_count, '
            . '(SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id) AS employee_count '
            . 'FROM branches b INNER JOIN companies c ON c.id = b.company_id '
            . 'ORDER BY c.name ASC, b.name ASC, b.code ASC, b.id ASC LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function listActive(): array
    {
        $statement = $this->connection->get()->query(
            "SELECT id, code, name, city, status FROM branches WHERE status = 'active' ORDER BY name ASC, id ASC",
        );

        return $statement->fetchAll();
    }

    public function listCompanies(): array
    {
        return $this->connection->get()->query(
            'SELECT id, code, name FROM companies ORDER BY name ASC, code ASC, id ASC',
        )->fetchAll();
    }

    public function findCompanyById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, code, name FROM companies WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $company = $statement->fetch();

        return $company === false ? null : $company;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT b.id, b.company_id, b.code, b.name, b.city, b.address, b.phone, b.status, '
            . 'b.created_at, b.updated_at, c.code AS company_code, c.name AS company_name, '
            . '(SELECT COUNT(*) FROM departments d WHERE d.branch_id = b.id) AS department_count, '
            . '(SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id) AS employee_count '
            . 'FROM branches b INNER JOIN companies c ON c.id = b.company_id WHERE b.id = :id',
        );
        $statement->execute(['id' => $id]);
        $branch = $statement->fetch();

        return $branch === false ? null : $branch;
    }

    public function codeExists(int $companyId, string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM branches WHERE company_id = :company_id AND code = :code';
        $parameters = ['company_id' => $companyId, 'code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }
        $statement = $this->connection->get()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function insert(BranchInput $input, string $createdAt, string $updatedAt): int
    {
        $statement = $this->connection->get()->prepare(
            'INSERT INTO branches '
            . '(company_id, code, name, city, address, phone, status, created_at, updated_at) '
            . 'VALUES (:company_id, :code, :name, :city, :address, :phone, \'active\', :created_at, :updated_at)',
        );

        try {
            $statement->execute([
                'company_id' => $input->companyId,
                'code' => $input->code,
                'name' => $input->name,
                'city' => $input->city,
                'address' => $input->address,
                'phone' => $input->phone,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        } catch (PDOException $exception) {
            $this->throwKnownDuplicate($exception);
            throw $exception;
        }

        return (int) $this->connection->get()->lastInsertId();
    }

    public function update(int $id, BranchInput $input, string $updatedAt): void
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE branches SET code = :code, name = :name, city = :city, address = :address, '
            . 'phone = :phone, updated_at = :updated_at WHERE id = :id',
        );

        try {
            $statement->execute([
                'id' => $id,
                'code' => $input->code,
                'name' => $input->name,
                'city' => $input->city,
                'address' => $input->address,
                'phone' => $input->phone,
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
            "UPDATE branches SET status = 'inactive', updated_at = :updated_at WHERE id = :id AND status = 'active'",
        );
        $statement->execute(['id' => $id, 'updated_at' => $updatedAt]);

        return $statement->rowCount() > 0;
    }

    private function throwKnownDuplicate(PDOException $exception): void
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        if ($sqlState === '23000' && (str_contains($message, 'uq_branches_company_code') || str_contains($message, 'code'))) {
            throw new BranchDuplicateException((int) $exception->getCode(), $exception);
        }
    }
}
