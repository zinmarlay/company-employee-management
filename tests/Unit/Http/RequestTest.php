<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testRequestNormalizesTransportValuesAndProvidesInputAccess(): void
    {
        $request = Request::fromValues(
            'post',
            '/employees/?page=2',
            ['page' => '2'],
            ['name' => 'Tanaka'],
            ['X-Request-Id' => 'request-123'],
            ['app_locale' => 'ja'],
        );

        self::assertSame('POST', $request->method());
        self::assertSame('/employees', $request->path());
        self::assertSame('2', $request->query('page'));
        self::assertSame('Tanaka', $request->body('name'));
        self::assertSame('request-123', $request->header('x-request-id'));
        self::assertSame('ja', $request->cookie('app_locale'));
    }

    public function testRouteParametersAreAttachedImmutably(): void
    {
        $request = Request::fromValues('GET', '/employees/EMP-001');
        $withParameters = $request->withRouteParameters(['employeeNumber' => 'EMP-001']);

        self::assertSame([], $request->routeParameters());
        self::assertSame('EMP-001', $withParameters->routeParameter('employeeNumber'));
        self::assertNotSame($request, $withParameters);
    }
}
