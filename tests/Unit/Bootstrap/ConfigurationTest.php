<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use App\Bootstrap\Configuration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function testProductionRequiresSecureUrlAndDisablesDebug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('APP_DEBUG must be false in production.');

        Configuration::fromEnvironment(__DIR__ . '/../../../config/app.php', [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'https://example.test',
        ]);
    }

    public function testProductionReportsHttpsRequirement(): void
    {
        $configuration = Configuration::fromEnvironment(__DIR__ . '/../../../config/app.php', [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://example.test',
        ]);

        self::assertTrue($configuration->isProduction());
        self::assertTrue($configuration->requiresHttps());
    }

    public function testProductionDatabaseConfigurationCannotFallBackToLocalDefaults(): void
    {
        $configuration = Configuration::fromEnvironment(__DIR__ . '/../../../config/app.php', [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://example.test',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_HOST must be explicitly configured in production.');
        $configuration->database();
    }
}
