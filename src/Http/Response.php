<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;

final class Response
{
    /** @param array<string, list<string>> $headers */
    private function __construct(
        private readonly string $body,
        private readonly int $statusCode,
        private readonly array $headers,
    ) {
    }

    /** @param array<string, string> $headers */
    public static function html(string $body, int $statusCode = 200, array $headers = []): self
    {
        return self::create($body, $statusCode, self::withDefaultContentType($headers, 'text/html; charset=utf-8'));
    }

    /** @param array<string, string> $headers */
    public static function text(string $body, int $statusCode = 200, array $headers = []): self
    {
        return self::create($body, $statusCode, self::withDefaultContentType($headers, 'text/plain; charset=utf-8'));
    }

    public static function redirect(string $location, int $statusCode = 303): self
    {
        if ($statusCode < 300 || $statusCode > 399) {
            throw new InvalidArgumentException('Redirect status code must be between 300 and 399.');
        }

        return self::create('', $statusCode, ['Location' => $location]);
    }

    /** @param array<string, string> $headers */
    public static function create(string $body, int $statusCode = 200, array $headers = []): self
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException('HTTP status code must be between 100 and 599.');
        }

        $validatedHeaders = [];

        foreach ($headers as $name => $value) {
            self::assertSafeHeader($name, $value);
            $validatedHeaders[$name] = [$value];
        }

        return new self($body, $statusCode, $validatedHeaders);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> The first value for each header name. */
    public function headers(): array
    {
        $headers = [];
        foreach ($this->headers as $name => $values) {
            $headers[$name] = $values[0] ?? '';
        }
        return $headers;
    }

    /** @return array<string, list<string>> */
    public function headerValues(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
            return $value[0] ?? null;
            }
        }

        return null;
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $existing = $this->headerName($name);
        $headers[$existing ?? $name] = [$value];

        return self::fromHeaderValues($this->body, $this->statusCode, $headers);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $existing = $this->headerName($name);
        $key = $existing ?? $name;
        $headers[$key] ??= [];
        $headers[$key][] = $value;

        return self::fromHeaderValues($this->body, $this->statusCode, $headers);
    }

    /** @param array<string, string> $headers @return array<string, string> */
    private static function withDefaultContentType(array $headers, string $contentType): array
    {
        foreach ($headers as $name => $_value) {
            if (strcasecmp($name, 'Content-Type') === 0) {
                return $headers;
            }
        }

        return ['Content-Type' => $contentType, ...$headers];
    }

    private static function assertSafeHeader(string $name, string $value): void
    {
        if ($name === '' || preg_match('/[\r\n:]/', $name) === 1) {
            throw new InvalidArgumentException('HTTP header names must be valid and must not contain CR, LF, or colon characters.');
        }

        if (preg_match('/[\r\n]/', $value) === 1) {
            throw new InvalidArgumentException('HTTP header values must not contain CR or LF characters.');
        }
    }

    /** @param array<string, list<string>> $headers */
    private static function fromHeaderValues(string $body, int $statusCode, array $headers): self
    {
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                self::assertSafeHeader($name, $value);
            }
        }

        return new self($body, $statusCode, $headers);
    }

    private function headerName(string $name): ?string
    {
        foreach (array_keys($this->headers) as $headerName) {
            if (strcasecmp($headerName, $name) === 0) {
                return $headerName;
            }
        }

        return null;
    }
}
