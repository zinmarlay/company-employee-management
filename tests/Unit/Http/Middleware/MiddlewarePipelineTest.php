<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CallableRequestHandler;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Middleware\RequestHandlerInterface;
use App\Http\Request;
use App\Http\Response;
use Closure;
use PHPUnit\Framework\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
    public function testMiddlewareRunsInBeforeAndAfterOrder(): void
    {
        $events = [];
        $record = static function (string $event) use (&$events): void {
            $events[] = $event;
        };

        $first = $this->middleware('A', $record);
        $second = $this->middleware('B', $record);
        $terminal = new CallableRequestHandler(static function (Request $request) use ($record): Response {
            $record('terminal');

            return Response::text('done');
        });

        $response = (new MiddlewarePipeline([$first, $second], $terminal))
            ->handle(Request::fromValues('GET', '/'));

        self::assertSame(200, $response->statusCode());
        self::assertSame(['A before', 'B before', 'terminal', 'B after', 'A after'], $events);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $terminalCalled = false;
        $shortCircuit = new class implements MiddlewareInterface {
            public function process(Request $request, RequestHandlerInterface $next): Response
            {
                return Response::text('stopped', 403);
            }
        };
        $terminal = new CallableRequestHandler(static function () use (&$terminalCalled): Response {
            $terminalCalled = true;

            return Response::text('terminal');
        });

        $response = (new MiddlewarePipeline([$shortCircuit], $terminal))
            ->handle(Request::fromValues('GET', '/'));

        self::assertSame(403, $response->statusCode());
        self::assertSame('stopped', $response->body());
        self::assertFalse($terminalCalled);
    }

    private function middleware(string $name, Closure $record): MiddlewareInterface
    {
        return new class($name, $record) implements MiddlewareInterface {
            public function __construct(
                private readonly string $name,
                private readonly Closure $record,
            ) {
            }

            public function process(Request $request, RequestHandlerInterface $next): Response
            {
                ($this->record)($this->name . ' before');
                $response = $next->handle($request);
                ($this->record)($this->name . ' after');

                return $response;
            }
        };
    }
}
