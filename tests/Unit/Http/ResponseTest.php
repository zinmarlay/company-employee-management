<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testResponseOwnsBodyStatusAndHeaders(): void
    {
        $response = Response::html('<p>Ready</p>', 201, ['X-Request-Id' => 'request-123']);

        self::assertSame('<p>Ready</p>', $response->body());
        self::assertSame(201, $response->statusCode());
        self::assertSame('text/html; charset=utf-8', $response->header('content-type'));
        self::assertSame('request-123', $response->header('X-Request-Id'));
    }

    public function testInvalidStatusCodesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Response::text('invalid', 700);
    }

    public function testUnsafeHeaderValuesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Response::text('unsafe', 200, ['X-Test' => "safe\r\nInjected: true"]);
    }
}
