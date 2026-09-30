<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityHeadersPolicy;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersPolicyTest extends TestCase
{
    public function testHtmlResponsesReceiveFrameAndCspProtection(): void
    {
        $response = (new SecurityHeadersPolicy())->apply(
            Response::html('<p>private</p>'),
            Request::fromValues('GET', '/employees')->withRouteAccess('user'),
        );

        self::assertStringContainsString("frame-ancestors 'none'", (string) $response->header('Content-Security-Policy'));
        self::assertStringContainsString("script-src 'self'", (string) $response->header('Content-Security-Policy'));
        self::assertSame('DENY', $response->header('X-Frame-Options'));
        self::assertSame('no-store, private', $response->header('Cache-Control'));
    }

    public function testJsonOrTextResponsesAreNotGivenHtmlPolicy(): void
    {
        $response = (new SecurityHeadersPolicy())->apply(
            Response::text('ok'),
            Request::fromValues('GET', '/health'),
        );

        self::assertNull($response->header('Content-Security-Policy'));
    }
}
