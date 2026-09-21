<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\ConnectionFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function testUsesExplicitPdoSafetyOptions(): void
    {
        self::assertSame(PDO::ERRMODE_EXCEPTION, ConnectionFactory::defaultOptions()[PDO::ATTR_ERRMODE]);
        self::assertSame(PDO::FETCH_ASSOC, ConnectionFactory::defaultOptions()[PDO::ATTR_DEFAULT_FETCH_MODE]);
        self::assertFalse(ConnectionFactory::defaultOptions()[PDO::ATTR_EMULATE_PREPARES]);
    }
}
