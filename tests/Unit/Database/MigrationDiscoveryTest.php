<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\Migration\MigrationDiscovery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationDiscoveryTest extends TestCase
{
    public function testDiscoversMigrationsInVersionOrder(): void
    {
        $discovery = new MigrationDiscovery(
            dirname(__DIR__, 2) . '/Fixtures/Migrations',
            'Tests\\Fixtures\\Migrations\\',
        );

        $definitions = $discovery->discover();

        self::assertSame(
            ['Version20260101000001First', 'Version20260101000002Second'],
            array_map(static fn ($definition): string => $definition->name(), $definitions),
        );
    }

    public function testRejectsInvalidMigrationFilename(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid migration filename');

        (new MigrationDiscovery(
            dirname(__DIR__, 2) . '/Fixtures/InvalidMigrations',
            'Tests\\Fixtures\\InvalidMigrations\\',
        ))->discover();
    }

    public function testRejectsDuplicateMigrationVersions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate migration version');

        (new MigrationDiscovery(
            dirname(__DIR__, 2) . '/Fixtures/DuplicateMigrations',
            'Tests\\Fixtures\\DuplicateMigrations\\',
        ))->discover();
    }

    public function testRejectsClassesThatDoNotImplementMigrationContract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement MigrationInterface');

        (new MigrationDiscovery(
            dirname(__DIR__, 2) . '/Fixtures/NonMigration',
            'Tests\\Fixtures\\NonMigration\\',
        ))->discover();
    }

    public function testMissingDirectoryIsAnEmptyMigrationSet(): void
    {
        $definitions = (new MigrationDiscovery(dirname(__DIR__, 2) . '/Fixtures/DoesNotExist'))->discover();

        self::assertSame([], $definitions);
    }
}
