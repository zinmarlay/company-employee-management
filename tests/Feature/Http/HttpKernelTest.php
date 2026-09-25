<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Bootstrap\ApplicationBootstrap;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpKernelTest extends TestCase
{
    public function testComposedSetupRouteReturnsTheSharedAdminShell(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();
        $response = $kernel->handle(Request::fromValues('GET', '/'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('text/html; charset=utf-8', $response->header('Content-Type'));
        self::assertStringContainsString('Welcome to Company Employee Management System', $response->body());
        self::assertStringContainsString('class="app-shell"', $response->body());
        self::assertStringContainsString('/assets/css/app.css', $response->body());
    }

    public function testUnknownRouteReturns404(): void
    {
        $router = new Router();
        $router->get('/setup', static fn (): Response => Response::text('setup'));
        $kernel = new HttpKernel($router, [], new ExceptionResponder(false));

        $response = $kernel->handle(Request::fromValues('GET', '/missing'));

        self::assertSame(404, $response->statusCode());
    }

    public function testUnsupportedMethodReturns405AndAllowHeader(): void
    {
        $router = new Router();
        $router->get('/setup', static fn (): Response => Response::text('setup'));
        $kernel = new HttpKernel($router, [], new ExceptionResponder(false));

        $response = $kernel->handle(Request::fromValues('POST', '/setup'));

        self::assertSame(405, $response->statusCode());
        self::assertSame('GET', $response->header('Allow'));
    }

    public function testProductionErrorResponseDoesNotExposeExceptionDetails(): void
    {
        $router = new Router();
        $router->get('/explode', static function (): Response {
            throw new RuntimeException('secret filesystem path /private/application.php');
        });
        $kernel = new HttpKernel($router, [], new ExceptionResponder(false));

        $response = $kernel->handle(Request::fromValues('GET', '/explode'));

        self::assertSame(500, $response->statusCode());
        self::assertStringNotContainsString('/private/application.php', $response->body());
        self::assertStringContainsString('unexpected application error', strtolower($response->body()));
    }

    public function testDevelopmentErrorResponseContainsBoundedDiagnosticInformation(): void
    {
        $router = new Router();
        $router->get('/explode', static function (): Response {
            throw new RuntimeException('controlled diagnostic');
        });
        $kernel = new HttpKernel($router, [], new ExceptionResponder(true));

        $response = $kernel->handle(Request::fromValues('GET', '/explode'));

        self::assertSame(500, $response->statusCode());
        self::assertStringContainsString('controlled diagnostic', $response->body());
    }
}
