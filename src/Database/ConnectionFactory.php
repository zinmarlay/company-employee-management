<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

final class ConnectionFactory
{
    public function __construct(private readonly DatabaseConfiguration $configuration)
    {
    }

    public function create(): PDO
    {
        try {
            return new PDO(
                $this->configuration->dsn(),
                $this->configuration->username(),
                $this->configuration->password(),
                self::defaultOptions(),
            );
        } catch (PDOException $exception) {
            throw new DatabaseConnectionException($exception);
        }
    }

    /**
     * @return array<int, mixed>
     */
    public static function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
