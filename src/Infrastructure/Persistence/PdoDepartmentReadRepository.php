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
