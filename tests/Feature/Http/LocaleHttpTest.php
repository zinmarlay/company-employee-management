<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Bootstrap\ApplicationBootstrap;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class LocaleHttpTest extends TestCase
{
    public function testJapaneseQueryLocaleChangesDocumentLanguageAndPersistsCookie(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();

        $response = $kernel->handle(Request::fromValues('GET', '/', ['lang' => 'ja']));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('<html lang="ja">', $response->body());
        self::assertStringContainsString('ダッシュボード', $response->body());
        self::assertStringContainsString('会社管理', $response->body());
        self::assertSame('app_locale=ja; Max-Age=31536000; Path=/; SameSite=Lax', $response->header('Set-Cookie'));
    }

    public function testValidCookieLocaleIsUsedWhenQueryLocaleIsAbsent(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();

        $response = $kernel->handle(Request::fromValues('GET', '/', [], [], [], ['app_locale' => 'ja']));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('<html lang="ja">', $response->body());
        self::assertNull($response->header('Set-Cookie'));
    }

    public function testUnsupportedQueryLocaleFallsBackToEnglish(): void
    {
        $kernel = (new ApplicationBootstrap(dirname(__DIR__, 3)))->boot();

        $response = $kernel->handle(Request::fromValues('GET', '/', ['lang' => 'fr'], [], [], ['app_locale' => 'ja']));

        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('<html lang="en">', $response->body());
        self::assertStringContainsString('Dashboard', $response->body());
    }
}
