<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Database\LazyPdoConnection;
use App\Domain\Organization\BranchReadRepositoryInterface;

final class PdoBranchReadRepository implements BranchReadRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listActive(): array
    {
        $statement = $this->connection->get()->query(
            "SELECT id, code, name, city, status FROM branches WHERE status = 'active' ORDER BY name ASC, id ASC",
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, code, name, city, status FROM branches WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $branch = $statement->fetch();

        return $branch === false ? null : $branch;
    }
}
