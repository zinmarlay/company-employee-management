<?php

declare(strict_types=1);

namespace App\Http\Routing;

use InvalidArgumentException;

final class Route
{
    private readonly string $normalizedPattern;
    private readonly string $pathRegex;

    /**
     * @param array<int, string> $methods
     * @param callable $handler
     */
    public function __construct(
        array $methods,
        string $pattern,
        private readonly mixed $handler,
    ) {
        if ($methods === []) {
            throw new InvalidArgumentException('A route must support at least one HTTP method.');
        }

        $normalizedMethods = [];

        foreach ($methods as $method) {
            $method = strtoupper(trim($method));

            if ($method === '') {
                throw new InvalidArgumentException('Route methods must not be empty.');
            }

            $normalizedMethods[$method] = true;
        }

        $this->methods = array_keys($normalizedMethods);
        $this->normalizedPattern = self::normalizePattern($pattern);
        $this->pathRegex = self::compilePattern($this->normalizedPattern);
    }

    /** @var array<int, string> */
    private readonly array $methods;

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function pattern(): string
    {
        return $this->normalizedPattern;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function supportsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    public function matchesPath(string $path): bool
    {
        return preg_match($this->pathRegex, $path) === 1;
    }

    /**
     * @return array<string, string>|null
     */
    public function parametersFor(string $path): ?array
    {
        if (preg_match($this->pathRegex, $path, $matches) !== 1) {
            return null;
        }

        $parameters = [];

        foreach ($matches as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }

            $decodedValue = rawurldecode($value);

            if (str_contains($decodedValue, '/')) {
                return null;
            }

            $parameters[$name] = $decodedValue;
        }

        return $parameters;
    }

    private static function normalizePattern(string $pattern): string
    {
        if ($pattern === '' || !str_starts_with($pattern, '/')) {
            throw new InvalidArgumentException('Route patterns must begin with /.');
        }

        return $pattern === '/' ? '/' : rtrim($pattern, '/');
    }

    private static function compilePattern(string $pattern): string
    {
        if ($pattern === '/') {
            return '~^/$~D';
        }

        $segments = explode('/', trim($pattern, '/'));
        $compiledSegments = [];
        $parameterNames = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $match) === 1) {
                $parameterName = $match[1];

                if (in_array($parameterName, $parameterNames, true)) {
                    throw new InvalidArgumentException('A route parameter name may appear only once in a route.');
                }

                $parameterNames[] = $parameterName;
                $compiledSegments[] = '(?P<' . $parameterName . '>[^/]+)';
                continue;
            }

            if (str_contains($segment, '{') || str_contains($segment, '}')) {
                throw new InvalidArgumentException('Route parameters must occupy a complete path segment.');
            }

            $compiledSegments[] = preg_quote($segment, '~');
        }

        return '~^/' . implode('/', $compiledSegments) . '$~D';
    }
}
