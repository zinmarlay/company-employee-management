<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\DispatchContractInput;
use App\Database\LazyPdoConnection;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use PDO;

final class PdoDispatchContractRepository implements DispatchContractRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare($this->selectSql() . ' WHERE dc.id = :id');
        $statement->execute(['id' => $id]);
        $contract = $statement->fetch();

        return $contract === false ? null : $contract;
    }

    public function findHistoryByEmployeeId(int $employeeId): array
    {
        $statement = $this->connection->get()->prepare(
            $this->selectSql()
            . ' WHERE dc.employee_id = :employee_id '
            . 'ORDER BY dc.start_date DESC, dc.end_date DESC, dc.id DESC',
        );
        $statement->execute(['employee_id' => $employeeId]);

        return $statement->fetchAll();
    }

    public function findByCompanyId(int $companyId): array
    {
        $statement = $this->connection->get()->prepare(
            $this->selectSql()
            . ' WHERE dc.dispatch_company_id = :company_id '
            . 'ORDER BY dc.start_date DESC, dc.end_date DESC, dc.id DESC',
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }

    public function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM dispatch_contracts '
            . 'WHERE employee_id = :employee_id '
            . 'AND start_date <= :end_date AND end_date >= :start_date';
        $parameters = [
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->get()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function insert(DispatchContractInput $input, string $createdAt, string $updatedAt): int
    {
        $statement = $this->connection->get()->prepare(
            'INSERT INTO dispatch_contracts '
            . '(employee_id, dispatch_company_id, start_date, end_date, created_at, updated_at) '
            . 'VALUES (:employee_id, :dispatch_company_id, :start_date, :end_date, :created_at, :updated_at)',
        );
        $statement->execute([
            'employee_id' => $input->employeeId,
            'dispatch_company_id' => $input->dispatchCompanyId,
            'start_date' => $input->startDate,
            'end_date' => $input->endDate,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        return (int) $this->connection->get()->lastInsertId();
    }

    public function update(int $id, DispatchContractInput $input, string $updatedAt): void
    {
        $statement = $this->connection->get()->prepare(
            'UPDATE dispatch_contracts SET employee_id = :employee_id, '
            . 'dispatch_company_id = :dispatch_company_id, start_date = :start_date, '
            . 'end_date = :end_date, updated_at = :updated_at WHERE id = :id',
        );
        $statement->execute([
            'id' => $id,
            'employee_id' => $input->employeeId,
            'dispatch_company_id' => $input->dispatchCompanyId,
            'start_date' => $input->startDate,
            'end_date' => $input->endDate,
            'updated_at' => $updatedAt,
        ]);
    }

    private function selectSql(): string
    {
        return 'SELECT dc.id, dc.employee_id, e.employee_code, '
            . 'CONCAT(e.last_name, \' \', e.first_name) AS employee_name, '
            . 'e.employee_type, dc.dispatch_company_id, dcc.code AS dispatch_company_code, '
            . 'dcc.name AS dispatch_company_name, dcc.status AS dispatch_company_status, '
            . 'dc.start_date, dc.end_date, dc.created_at, dc.updated_at '
            . 'FROM dispatch_contracts dc '
            . 'INNER JOIN employees e ON e.id = dc.employee_id '
            . 'INNER JOIN dispatch_companies dcc ON dcc.id = dc.dispatch_company_id';
    }
}
