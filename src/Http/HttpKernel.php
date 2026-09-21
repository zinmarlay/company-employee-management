<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\CallableRequestHandler;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Routing\Router;
use RuntimeException;
use Throwable;

final class HttpKernel
{
    /**
     * @param array<int, MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly Router $router,
        private readonly array $middlewares,
        private readonly ExceptionResponder $exceptionResponder,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request);
            $routeRequest = $request->withRouteParameters($match->parameters());
            $handler = $match->route()->handler();

            $terminalHandler = new CallableRequestHandler(
                function (Request $request) use ($handler): Response {
                    $response = $handler($request);

                    if (!$response instanceof Response) {
                        throw new RuntimeException('A route handler must return a Response.');
                    }

                    return $response;
                },
            );

            return (new MiddlewarePipeline($this->middlewares, $terminalHandler))->handle($routeRequest);
        } catch (Throwable $exception) {
            return $this->exceptionResponder->respond($exception);
        }
    }
}
