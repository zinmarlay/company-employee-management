<?php

declare(strict_types=1);

namespace App\Database;

use RuntimeException;

final class DatabaseConnectionException extends RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The database connection could not be established.', 0, $previous);
    }
}
