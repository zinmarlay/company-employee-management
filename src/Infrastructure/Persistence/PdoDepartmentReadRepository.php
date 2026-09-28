<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Database\LazyPdoConnection;
use App\Domain\Organization\DepartmentReadRepositoryInterface;

final class PdoDepartmentReadRepository implements DepartmentReadRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listActive(): array
    {
        $statement = $this->connection->get()->query(
            "SELECT id, branch_id, code, name, status FROM departments "
            . "WHERE status = 'active' ORDER BY branch_id ASC, name ASC, id ASC",
        );

        return $statement->fetchAll();
    }

    public function listForSearch(): array
    {
        $statement = $this->connection->get()->query(
            'SELECT d.id, d.branch_id, d.code, d.name, d.status, '
            . 'b.code AS branch_code, b.name AS branch_name, b.status AS branch_status '
            . 'FROM departments d INNER JOIN branches b ON b.id = d.branch_id '
            . 'ORDER BY b.name ASC, d.name ASC, d.id ASC',
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, branch_id, code, name, status FROM departments WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $department = $statement->fetch();

        return $department === false ? null : $department;
    }
}
