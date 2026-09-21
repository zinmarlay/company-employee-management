<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\DatabaseConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DatabaseConfigurationTest extends TestCase
{
    public function testBuildsMysqlConfigurationWithSafeDefaults(): void
    {
        $configuration = DatabaseConfiguration::fromEnvironment([
            'DB_DATABASE' => 'company_employee_management_test',
            'DB_USERNAME' => 'test_user',
            'DB_PASSWORD' => 'secret',
        ]);

        self::assertSame('mysql', $configuration->driver());
        self::assertSame('127.0.0.1', $configuration->host());
        self::assertSame(3306, $configuration->port());
        self::assertSame('utf8mb4', $configuration->charset());
        self::assertSame(
            'mysql:host=127.0.0.1;port=3306;dbname=company_employee_management_test;charset=utf8mb4',
            $configuration->dsn(),
        );
    }

    public function testPasswordMayBeExplicitlyConfiguredAsAnEmptyString(): void
    {
        $configuration = DatabaseConfiguration::fromEnvironment([
            'DB_DATABASE' => 'company_employee_management_test',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ]);

        self::assertSame('', $configuration->password());
    }

    public function testRequiredConnectionValuesCannotBeOmitted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_DATABASE must be explicitly configured.');

        DatabaseConfiguration::fromEnvironment([
            'DB_USERNAME' => 'test_user',
            'DB_PASSWORD' => 'secret',
        ]);
    }

    public function testOnlyUtf8mb4IsAccepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_CHARSET must be utf8mb4.');

        DatabaseConfiguration::fromEnvironment([
            'DB_DATABASE' => 'company_employee_management_test',
            'DB_USERNAME' => 'test_user',
            'DB_PASSWORD' => 'secret',
            'DB_CHARSET' => 'latin1',
        ]);
    }
}
