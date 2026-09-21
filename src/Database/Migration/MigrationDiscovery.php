<?php

declare(strict_types=1);

namespace App\Database\Migration;

use InvalidArgumentException;
use RuntimeException;

final class MigrationDiscovery
{
    public function __construct(
        private readonly string $directory,
        private readonly string $namespace = 'Database\\Migrations\\',
    ) {
    }

    /**
     * @return array<int, MigrationDefinition>
     */
    public function discover(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);
        $definitions = [];
        $versions = [];

        foreach ($files as $file) {
            $basename = pathinfo($file, PATHINFO_FILENAME);

            if (preg_match('/^Version(\d{14})([A-Z][A-Za-z0-9]*)$/', $basename, $matches) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid migration filename: %s', basename($file)));
            }

            $version = $matches[1];

            if (isset($versions[$version])) {
                throw new InvalidArgumentException(sprintf('Duplicate migration version: %s', $version));
            }

            $class = $this->namespace . $basename;

            if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Migration class could not be loaded: %s', $class));
            }

            $migration = new $class();

            if (!$migration instanceof MigrationInterface) {
                throw new InvalidArgumentException(sprintf('Migration class must implement MigrationInterface: %s', $class));
            }

            $versions[$version] = true;
            $definitions[] = new MigrationDefinition($basename, $version, $migration);
        }

        usort(
            $definitions,
            static fn (MigrationDefinition $left, MigrationDefinition $right): int
                => $left->version() <=> $right->version(),
        );

        return $definitions;
    }
}
