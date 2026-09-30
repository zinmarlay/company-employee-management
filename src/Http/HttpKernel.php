<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\CallableRequestHandler;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Routing\Router;
use App\Http\Routing\MethodNotAllowedException;
use App\Http\Routing\NotFoundException;
use App\Logging\LogContext;
use App\Logging\LoggerInterface;
use App\Logging\NullLogger;
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
        ?LoggerInterface $logger = null,
        ?SecurityHeadersPolicy $securityHeaders = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->securityHeaders = $securityHeaders ?? new SecurityHeadersPolicy();
    }

    private readonly LoggerInterface $logger;
    private readonly SecurityHeadersPolicy $securityHeaders;

    public function handle(Request $request): Response
    {
        $routeRequest = $request;
        try {
            $match = $this->router->match($request);
            $routeRequest = $request
                ->withRouteParameters($match->parameters())
                ->withRouteAccess($match->route()->access());
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

            return $this->securityHeaders->apply(
                (new MiddlewarePipeline($this->middlewares, $terminalHandler))->handle($routeRequest),
                $routeRequest,
            );
        } catch (Throwable $exception) {
            if (!$exception instanceof NotFoundException && !$exception instanceof MethodNotAllowedException) {
                $this->logger->error('unexpected_exception', LogContext::request($routeRequest, [
                    'outcome' => 'exception',
                    'status' => 500,
                    'exception_class' => $exception::class,
                ]));
            }
            return $this->securityHeaders->apply($this->exceptionResponder->respond($exception), $routeRequest);
        }
    }
}
