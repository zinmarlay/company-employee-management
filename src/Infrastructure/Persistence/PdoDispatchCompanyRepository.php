<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\DispatchCompanyInput;
use App\Database\LazyPdoConnection;
use App\Domain\Dispatch\DispatchCompanyDuplicateException;
use App\Domain\Dispatch\DispatchCompanyRepositoryInterface;
use PDOException;

final class PdoDispatchCompanyRepository implements DispatchCompanyRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listBasic(int $limit): array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT dc.id, dc.code, dc.name, dc.phone, dc.email, dc.address, dc.status, '
            . 'dc.created_at, dc.updated_at, '
            . '(SELECT COUNT(*) FROM dispatch_contracts dct WHERE dct.dispatch_company_id = dc.id) AS contract_count '
            . 'FROM dispatch_companies dc '
            . 'ORDER BY dc.name ASC, dc.code ASC, dc.id ASC LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, code, name, phone, email, address, status, created_at, updated_at '
            . 'FROM dispatch_companies WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $company = $statement->fetch();

        return $company === false ? null : $company;
    }

    public function insert(DispatchCompanyInput $input, string $createdAt, string $updatedAt): int
    {
        $statement = $this->connection->get()->prepare(
            'INSERT INTO dispatch_companies '
            . '(code, name, phone, email, address, status, created_at, updated_at) '
            . 'VALUES (:code, :name, :phone, :email, :address, \'active\', :created_at, :updated_at)',
        );

        try {
            $statement->execute([
                'code' => $input->code,
                'name' => $input->name,
                'phone' => $input->phone,
                'email' => $input->email,
                'address' => $input->address,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        } catch (PDOException $exception) {
            $this->throwKnownDuplicate($exception);
            throw $exception;
        }

        return (int) $this->connection->get()->lastInsertId();
    }

    public function update(int $id, DispatchCompanyInput $input, string $updatedAt): void
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE dispatch_companies SET code = :code, name = :name, phone = :phone, '
            . 'email = :email, address = :address, updated_at = :updated_at WHERE id = :id',
        );

        try {
            $statement->execute([
                'id' => $id,
                'code' => $input->code,
                'name' => $input->name,
                'phone' => $input->phone,
                'email' => $input->email,
                'address' => $input->address,
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
            "UPDATE dispatch_companies SET status = 'inactive', updated_at = :updated_at "
            . "WHERE id = :id AND status = 'active'",
        );
        $statement->execute(['id' => $id, 'updated_at' => $updatedAt]);

        return $statement->rowCount() > 0;
    }

    private function throwKnownDuplicate(PDOException $exception): void
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        if ($sqlState === '23000' && (str_contains($message, 'uq_dispatch_companies_code') || str_contains($message, 'code'))) {
            throw new DispatchCompanyDuplicateException('code', (int) $exception->getCode(), $exception);
        }
    }
}
