<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use App\Bootstrap\EnvironmentLoader;
use PHPUnit\Framework\TestCase;

final class EnvironmentLoaderTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->rememberEnvironment('DOTENV_TEST_VALUE');
        $this->clearEnvironment('DOTENV_TEST_VALUE');
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment('DOTENV_TEST_VALUE');
        parent::tearDown();
    }

    public function testLoadsDotEnvValuesIntoRuntimeEnvironment(): void
    {
        $directory = $this->temporaryDirectory();
        file_put_contents($directory . '/.env', "DOTENV_TEST_VALUE=from-file\n");

        EnvironmentLoader::load($directory);

        self::assertSame('from-file', getenv('DOTENV_TEST_VALUE'));
        self::assertSame('from-file', $_ENV['DOTENV_TEST_VALUE'] ?? null);

        $this->removeTemporaryDirectory($directory);
    }

    public function testExplicitRuntimeEnvironmentTakesPrecedenceOverDotEnv(): void
    {
        $directory = $this->temporaryDirectory();
        file_put_contents($directory . '/.env', "DOTENV_TEST_VALUE=from-file\n");
        putenv('DOTENV_TEST_VALUE=from-runtime');
        $_ENV['DOTENV_TEST_VALUE'] = 'from-runtime';

        EnvironmentLoader::load($directory);

        self::assertSame('from-runtime', getenv('DOTENV_TEST_VALUE'));
        self::assertSame('from-runtime', $_ENV['DOTENV_TEST_VALUE'] ?? null);

        $this->removeTemporaryDirectory($directory);
    }

    private function rememberEnvironment(string $key): void
    {
        $this->originalEnvironment[$key] = getenv($key);
    }

    private function clearEnvironment(string $key): void
    {
        putenv($key);
        unset($_ENV[$key]);
    }

    private function restoreEnvironment(string $key): void
    {
        $original = $this->originalEnvironment[$key] ?? false;

        if ($original === false) {
            putenv($key);
            unset($_ENV[$key]);

            return;
        }

        putenv($key . '=' . $original);
        $_ENV[$key] = $original;
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/company-employee-management-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);

        return $directory;
    }

    private function removeTemporaryDirectory(string $directory): void
    {
        unlink($directory . '/.env');
        rmdir($directory);
    }
}
