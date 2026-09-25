<?php

declare(strict_types=1);

namespace App\Database\Seed;

use InvalidArgumentException;

final class DevelopmentSeedSafety
{
    public static function assertAllowed(string $environment, string $database): void
    {
        if ($environment !== 'local') {
            throw new InvalidArgumentException('Development seeding is only allowed when APP_ENV=local.');
        }

        $database = trim($database);
        $normalized = strtolower($database);

        if ($database === '') {
            throw new InvalidArgumentException('Development seeding requires a non-empty DB_DATABASE value.');
        }

        if (str_ends_with($normalized, '_test') || preg_match('/(^|[_-])(test|testing|ci)([_-]|$)/', $normalized) === 1) {
            throw new InvalidArgumentException(sprintf(
                'Development seeding refuses test-like database "%s".',
                $database,
            ));
        }
    }
}
