<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Database\DatabaseConfiguration;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

final class Configuration
{
    /** @var array<string, mixed> */
    private readonly array $databaseDefaults;

    /** @param array<string, mixed> $values */
    private function __construct(
        private readonly array $values,
        private readonly array $environment,
        array $databaseDefaults,
    ) {
        $this->databaseDefaults = $databaseDefaults;
    }

    /**
     * @param array<string, mixed> $environment
     */
    public static function fromEnvironment(string $configFile, array $environment = []): self
    {
        if (!extension_loaded('mbstring')) {
            throw new RuntimeException('The mbstring extension is required.');
        }

        $defaults = require $configFile;

        if (!is_array($defaults)) {
            throw new RuntimeException('Application configuration must return an array.');
        }

        $values = [
            'app_name' => self::stringValue('APP_NAME', $defaults['app_name'] ?? null, $environment),
            'app_env' => self::environmentValue($defaults['app_env'] ?? 'local', $environment),
            'app_debug' => self::booleanValue('APP_DEBUG', $defaults['app_debug'] ?? false, $environment),
            'app_url' => self::stringValue('APP_URL', $defaults['app_url'] ?? null, $environment),
            'app_timezone' => self::timezoneValue($defaults['app_timezone'] ?? 'UTC', $environment),
        ];

        $databaseDefaults = $defaults['database'] ?? [];

        if (!is_array($databaseDefaults)) {
            throw new RuntimeException('Database configuration defaults must be an array.');
        }

        $configuration = new self($values, $environment, $databaseDefaults);
        $configuration->validateProduction();
        return $configuration;
    }

    public function name(): string
    {
        return $this->values['app_name'];
    }

    public function environment(): string
    {
        return $this->values['app_env'];
    }

    public function isDebug(): bool
    {
        return $this->values['app_debug'];
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function requiresHttps(): bool
    {
        return $this->isProduction();
    }

    public function url(): string
    {
        return $this->values['app_url'];
    }

    public function timezone(): string
    {
        return $this->values['app_timezone'];
    }

    public function database(): DatabaseConfiguration
    {
        return DatabaseConfiguration::fromEnvironment($this->environment, $this->databaseDefaults, $this->isProduction());
    }

    private static function environmentValue(mixed $default, array $environment): string
    {
        $value = self::environmentVariable('APP_ENV', $default, $environment);

        if (!is_string($value) || !in_array($value, ['local', 'test', 'production'], true)) {
            throw new InvalidArgumentException('APP_ENV must be local, test, or production.');
        }

        return $value;
    }

    private static function booleanValue(string $key, mixed $default, array $environment): bool
    {
        $value = self::environmentVariable($key, $default, $environment);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => throw new InvalidArgumentException(sprintf('%s must be a boolean value.', $key)),
            };
        }

        throw new InvalidArgumentException(sprintf('%s must be a boolean value.', $key));
    }

    private static function stringValue(string $key, mixed $default, array $environment): string
    {
        $value = self::environmentVariable($key, $default, $environment);

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string.', $key));
        }

        return trim($value);
    }

    private static function timezoneValue(mixed $default, array $environment): string
    {
        $timezone = self::stringValue('APP_TIMEZONE', $default, $environment);

        try {
            new DateTimeZone($timezone);
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('APP_TIMEZONE must be a valid timezone.', 0, $exception);
        }

        return $timezone;
    }

    private static function environmentVariable(string $key, mixed $default, array $environment): mixed
    {
        if (array_key_exists($key, $environment)) {
            return $environment[$key];
        }

        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }

    private function validateProduction(): void
    {
        if (!$this->isProduction()) {
            return;
        }

        if ($this->isDebug()) {
            throw new InvalidArgumentException('APP_DEBUG must be false in production.');
        }

        $url = filter_var($this->url(), FILTER_VALIDATE_URL);
        if ($url === false || strtolower((string) parse_url($this->url(), PHP_URL_SCHEME)) !== 'https') {
            throw new InvalidArgumentException('APP_URL must use HTTPS in production.');
        }
    }
}
