<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\Seed\DevelopmentSeedSafety;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DevelopmentSeedSafetyTest extends TestCase
{
    public function testOnlyLocalNonTestDatabaseNamesAreAllowed(): void
    {
        DevelopmentSeedSafety::assertAllowed('local', 'company_employee_management');

        self::expectNotToPerformAssertions();
    }

    /** @dataProvider unsafeConfigurationProvider */
    public function testUnsafeConfigurationIsRejected(string $environment, string $database, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        DevelopmentSeedSafety::assertAllowed($environment, $database);
    }

    /** @return array<string, array{string, string, string}> */
    public static function unsafeConfigurationProvider(): array
    {
        return [
            'test environment' => ['test', 'company_employee_management', 'APP_ENV=local'],
            'production environment' => ['production', 'company_employee_management', 'APP_ENV=local'],
            'empty database' => ['local', '', 'non-empty DB_DATABASE'],
            'test suffix' => ['local', 'company_employee_management_test', 'test-like database'],
            'test marker' => ['local', 'company_test_copy', 'test-like database'],
        ];
    }
}
