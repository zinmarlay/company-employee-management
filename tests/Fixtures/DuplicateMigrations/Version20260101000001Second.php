<?php

declare(strict_types=1);

namespace Tests\Fixtures\DuplicateMigrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260101000001Second implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
    }

    public function down(PDO $pdo): void
    {
    }
}
