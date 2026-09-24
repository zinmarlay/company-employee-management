<?php

declare(strict_types=1);

namespace App\Database;

use App\Bootstrap\Configuration;
use PDO;

final class LazyPdoConnection
{
    private ?PDO $connection = null;

    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function get(): PDO
    {
        if (!$this->connection instanceof PDO) {
            $this->connection = (new ConnectionFactory($this->configuration->database()))->create();
        }

        return $this->connection;
    }
}
