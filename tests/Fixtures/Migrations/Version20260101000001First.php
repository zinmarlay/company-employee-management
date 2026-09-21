<?php

declare(strict_types=1);

namespace Tests\Fixtures\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;

final class Version20260101000001First implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
    }

    public function down(PDO $pdo): void
    {
    }
}
