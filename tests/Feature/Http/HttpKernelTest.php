<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Bootstrap\ApplicationBootstrap;
use App\Http\ExceptionResponder;
use App\Http\HttpKernel;
use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityErrorResponder;
use App\Http\Middleware\AuthorizationMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\Routing\Router;
use App\Http\View\ViewRenderer;
use App\Security\AuthenticatedUser;
use App\Security\CsrfTokenManager;
use App\Security\SessionManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpKernelTest extends TestCase
{
    public function testComposedProtectedLandingRouteRedirectsUnauthenticatedUsersToLogin(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();
        $response = $kernel->handle(Request::fromValues('GET', '/'));

        self::assertSame(303, $response->statusCode());
        self::assertSame('/login', $response->header('Location'));
    }

    public function testComposedLoginPageIsPublicAndProvidesAHiddenCsrfToken(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();
        $response = $kernel->handle(Request::fromValues('GET', '/login'));

        self::assertSame(200, $response->statusCode());
        self::assertMatchesRegularExpression('/name="csrf_token" value="[a-f0-9]{64}"/', $response->body());
        self::assertStringContainsString('name="password"', $response->body());
        self::assertStringNotContainsString('value="long-enough-password"', $response->body());
        self::assertStringNotContainsString('Administrator', $response->body());
        self::assertStringNotContainsString('Read-only user', $response->body());
        self::assertStringNotContainsString('Sign out', $response->body());
        self::assertStringNotContainsString('href="/employees"', $response->body());
    }

    public function testComposedPostWithoutCsrfIsRejectedBeforeLoginProcessing(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();
        $response = $kernel->handle(Request::fromValues('POST', '/login', [], [
            'email' => 'unknown@example.test',
            'password' => 'not-a-real-password',
        ]));

        self::assertSame(403, $response->statusCode());
        self::assertStringContainsString('Request rejected', $response->body());
    }

    public function testUserCannotAccessSystemUserActivationRoute(): void
    {
        $router = new Router();
        $router->get('/system-users/{id}/activate', static fn (): Response => throw new \LogicException('controller must not run'), 'admin');
        $router->post('/system-users/{id}/activate', static fn (): Response => throw new \LogicException('controller must not run'), 'admin');
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views');
        $kernel = new HttpKernel(
            $router,
            [new AuthorizationMiddleware(new SecurityErrorResponder($views))],
            new ExceptionResponder(false),
        );

        $getResponse = $kernel->handle(
            Request::fromValues('GET', '/system-users/7/activate')
                ->withAuthenticatedUser(new AuthenticatedUser(2, 'Read Only', 'user@example.test', 'USER')),
        );
        $postResponse = $kernel->handle(
            Request::fromValues('POST', '/system-users/7/activate', [], ['csrf_token' => 'not-used'])
                ->withAuthenticatedUser(new AuthenticatedUser(2, 'Read Only', 'user@example.test', 'USER')),
        );

        self::assertSame(403, $getResponse->statusCode());
        self::assertSame(403, $postResponse->statusCode());
    }

    public function testSystemUserActivationPostRequiresCsrfAndAllowsValidCsrf(): void
    {
        $router = new Router();
        $router->post('/system-users/{id}/activate', static fn (): Response => Response::text('activated'), 'admin');
        $views = new ViewRenderer(dirname(__DIR__, 3) . '/resources/views');
        $session = new SessionManager();
        $tokens = new CsrfTokenManager($session);
        $kernel = new HttpKernel(
            $router,
            [
                new SessionMiddleware($session),
                new AuthorizationMiddleware(new SecurityErrorResponder($views)),
                new CsrfMiddleware($tokens, new SecurityErrorResponder($views)),
            ],
            new ExceptionResponder(false),
        );

        try {
            $user = new AuthenticatedUser(1, 'Administrator', 'admin@example.test', 'ADMIN');
            $missing = $kernel->handle(
                Request::fromValues('POST', '/system-users/7/activate')->withAuthenticatedUser($user),
            );
            self::assertSame(403, $missing->statusCode());

            $valid = $kernel->handle(
                Request::fromValues(
                    'POST',
                    '/system-users/7/activate',
                    [],
                    ['csrf_token' => $tokens->token()],
                )->withAuthenticatedUser($user),
            );
            self::assertSame(200, $valid->statusCode());
        } finally {
            $session->destroy();
        }
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
