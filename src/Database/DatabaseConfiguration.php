<?php

declare(strict_types=1);

namespace App\Database;

use InvalidArgumentException;

final class DatabaseConfiguration
{
    private function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly string $charset,
        private readonly string $driver,
    ) {
    }

    /**
     * @param array<string, mixed> $environment
     * @param array<string, mixed> $defaults
     */
    public static function fromEnvironment(array $environment = [], array $defaults = []): self
    {
        $host = self::stringValue('DB_HOST', $environment, $defaults['host'] ?? '127.0.0.1');
        $port = self::portValue($environment, $defaults['port'] ?? 3306);
        $database = self::requiredStringValue('DB_DATABASE', $environment);
        $username = self::requiredStringValue('DB_USERNAME', $environment);
        $password = self::requiredEnvironmentValue('DB_PASSWORD', $environment);
        $charset = self::charsetValue($environment, $defaults['charset'] ?? 'utf8mb4');

        return new self($host, $port, $database, $username, $password, $charset, 'mysql');
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function database(): string
    {
        return $this->database;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function dsn(): string
    {
        return sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->driver,
            $this->host,
            $this->port,
            $this->database,
            $this->charset,
        );
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function stringValue(string $key, array $environment, mixed $default): string
    {
        $value = self::environmentValue($key, $environment, $default);

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string.', $key));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function requiredStringValue(string $key, array $environment): string
    {
        if (!self::hasEnvironmentValue($key, $environment)) {
            throw new InvalidArgumentException(sprintf('%s must be explicitly configured.', $key));
        }

        return self::stringValue($key, $environment, null);
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function requiredEnvironmentValue(string $key, array $environment): string
    {
        if (!self::hasEnvironmentValue($key, $environment)) {
            throw new InvalidArgumentException(sprintf('%s must be explicitly configured.', $key));
        }

        $value = self::environmentValue($key, $environment, null);

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function portValue(array $environment, mixed $default): int
    {
        $value = self::environmentValue('DB_PORT', $environment, $default);

        if (is_int($value)) {
            $port = $value;
        } elseif (is_string($value) && ctype_digit($value)) {
            $port = (int) $value;
        } else {
            throw new InvalidArgumentException('DB_PORT must be an integer.');
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('DB_PORT must be between 1 and 65535.');
        }

        return $port;
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function charsetValue(array $environment, mixed $default): string
    {
        $charset = self::environmentValue('DB_CHARSET', $environment, $default);

        if ($charset !== 'utf8mb4') {
            throw new InvalidArgumentException('DB_CHARSET must be utf8mb4.');
        }

        return $charset;
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function environmentValue(string $key, array $environment, mixed $default): mixed
    {
        if (array_key_exists($key, $environment)) {
            return $environment[$key];
        }

        $value = getenv($key);

        return $value === false ? $default : $value;
    }

    /**
     * @param array<string, mixed> $environment
     */
    private static function hasEnvironmentValue(string $key, array $environment): bool
    {
        if (array_key_exists($key, $environment)) {
            return true;
        }

        return getenv($key) !== false;
    }
}
