<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed> $queryParameters
     * @param array<string, mixed> $bodyParameters
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     * @param array<string, string> $routeParameters
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $queryParameters,
        private readonly array $bodyParameters,
        private readonly array $headers,
        private readonly array $cookies,
        private readonly array $routeParameters,
    ) {
    }

    public static function fromGlobals(): self
    {
        return self::fromValues(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            is_array($_GET) ? $_GET : [],
            is_array($_POST) ? $_POST : [],
            self::headersFromServer($_SERVER),
            is_array($_COOKIE) ? $_COOKIE : [],
        );
    }

    /**
     * @param array<string, mixed> $queryParameters
     * @param array<string, mixed> $bodyParameters
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    public static function fromValues(
        string $method,
        string $uri,
        array $queryParameters = [],
        array $bodyParameters = [],
        array $headers = [],
        array $cookies = [],
    ): self {
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = $value;
        }

        return new self(
            strtoupper(trim($method)),
            self::normalizePath($uri),
            $queryParameters,
            $bodyParameters,
            $normalizedHeaders,
            $cookies,
            [],
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParameters(): array
    {
        return $this->queryParameters;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParameters[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return $this->bodyParameters;
    }

    public function body(string $key, mixed $default = null): mixed
    {
        return $this->bodyParameters[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, string>
     */
    public function routeParameters(): array
    {
        return $this->routeParameters;
    }

    public function routeParameter(string $name, ?string $default = null): ?string
    {
        return $this->routeParameters[$name] ?? $default;
    }

    /**
     * @param array<string, string> $routeParameters
     */
    public function withRouteParameters(array $routeParameters): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->queryParameters,
            $this->bodyParameters,
            $this->headers,
            $this->cookies,
            $routeParameters,
        );
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $name => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($name, 5));
                $headers[$headerName] = $value;
                continue;
            }

            if ($name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
                $headerName = str_replace('_', '-', $name);
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
