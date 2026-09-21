<?php

declare(strict_types=1);

namespace App\Database\Migration;

final class MigrationDefinition
{
    public function __construct(
        private readonly string $name,
        private readonly string $version,
        private readonly MigrationInterface $migration,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function migration(): MigrationInterface
    {
        return $this->migration;
    }
}
