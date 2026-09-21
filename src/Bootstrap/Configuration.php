<?php

declare(strict_types=1);

namespace App\Bootstrap;

use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

final class Configuration
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private readonly array $values)
    {
    }

    /**
     * @param array<string, mixed> $environment
     */
    public static function fromEnvironment(string $configFile, array $environment = []): self
    {
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

        return new self($values);
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

    public function url(): string
    {
        return $this->values['app_url'];
    }

    public function timezone(): string
    {
        return $this->values['app_timezone'];
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
}
