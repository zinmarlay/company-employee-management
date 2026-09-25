<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Dotenv\Dotenv;

final class EnvironmentLoader
{
    public static function load(string $projectRoot): void
    {
        // Configuration::fromEnvironment() intentionally reads getenv(), so
        // use Dotenv's immutable repository with the putenv adapter. Existing
        // process variables remain authoritative and are never overwritten.
        Dotenv::createUnsafeImmutable($projectRoot)->safeLoad();
    }
}
