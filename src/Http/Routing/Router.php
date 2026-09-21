<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Request;
use InvalidArgumentException;

final class Router
{
    /** @var array<int, Route> */
    private array $routes = [];

    /**
     * @param array<int, string> $methods
     * @param callable $handler
     */
    public function add(array $methods, string $pattern, mixed $handler): void
    {
        if (!is_callable($handler)) {
            throw new InvalidArgumentException('A route handler must be callable.');
        }

        $route = new Route($methods, $pattern, $handler);

        foreach ($route->methods() as $method) {
            foreach ($this->routes as $existingRoute) {
                if ($existingRoute->pattern() === $route->pattern()
                    && in_array($method, $existingRoute->methods(), true)
                ) {
                    throw new InvalidArgumentException('A route is already registered for this method and pattern.');
                }
            }
        }

        $this->routes[] = $route;
    }

    /** @param callable $handler */
    public function get(string $pattern, mixed $handler): void
    {
        $this->add(['GET'], $pattern, $handler);
    }

    /** @param callable $handler */
    public function post(string $pattern, mixed $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function match(Request $request): RouteMatch
    {
        $pathMatches = [];

        foreach ($this->routes as $route) {
            if (!$route->matchesPath($request->path())) {
                continue;
            }

            $parameters = $route->parametersFor($request->path());

            if ($parameters === null) {
                continue;
            }

            $pathMatches[] = [$route, $parameters];

            if ($route->supportsMethod($request->method())) {
                return new RouteMatch($route, $parameters);
            }
        }

        if ($pathMatches === []) {
            throw new NotFoundException($request->path());
        }

        $allowedMethods = [];

        foreach ($pathMatches as [$route]) {
            foreach ($route->methods() as $method) {
                if (!in_array($method, $allowedMethods, true)) {
                    $allowedMethods[] = $method;
                }
            }
        }

        throw new MethodNotAllowedException($allowedMethods, $request->path());
    }
}
