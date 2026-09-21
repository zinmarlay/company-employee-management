<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use InvalidArgumentException;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param array<int, MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly RequestHandlerInterface $terminalHandler,
    ) {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException('Every pipeline entry must implement MiddlewareInterface.');
            }
        }
    }

    public function handle(Request $request): Response
    {
        return $this->dispatch($request, 0);
    }

    private function dispatch(Request $request, int $index): Response
    {
        if (!isset($this->middlewares[$index])) {
            return $this->terminalHandler->handle($request);
        }

        $next = new CallableRequestHandler(
            fn (Request $nextRequest): Response => $this->dispatch($nextRequest, $index + 1),
        );

        return $this->middlewares[$index]->process($request, $next);
    }
}
