<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Routing;

use App\Http\Request;
use App\Http\Routing\MethodNotAllowedException;
use App\Http\Routing\NotFoundException;
use App\Http\Routing\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testExactAndNamedParameterRoutesMatch(): void
    {
        $router = new Router();
        $router->get('/employees/{employeeNumber}', static fn (): never => throw new \LogicException());

        $match = $router->match(Request::fromValues('get', '/employees/EMP-001'));

        self::assertSame('/employees/{employeeNumber}', $match->route()->pattern());
        self::assertSame(['employeeNumber' => 'EMP-001'], $match->parameters());
    }

    public function testUnknownPathRaisesNotFound(): void
    {
        $router = new Router();
        $router->get('/setup', static fn (): never => throw new \LogicException());

        $this->expectException(NotFoundException::class);

        $router->match(Request::fromValues('GET', '/missing'));
    }

    public function testUnsupportedMethodRaisesMethodNotAllowedWithAllowedMethods(): void
    {
        $router = new Router();
        $router->get('/setup', static fn (): never => throw new \LogicException());

        try {
            $router->match(Request::fromValues('POST', '/setup'));
            self::fail('Expected a method-not-allowed exception.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['GET'], $exception->allowedMethods());
        }
    }

    public function testDuplicateMethodAndPatternRegistrationIsRejected(): void
    {
        $router = new Router();
        $handler = static fn (): never => throw new \LogicException();
        $router->get('/setup', $handler);

        $this->expectException(InvalidArgumentException::class);

        $router->get('/setup', $handler);
    }
}
