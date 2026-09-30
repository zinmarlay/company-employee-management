<?php

declare(strict_types=1);

namespace Tests\Unit\Http\View;

use App\Http\View\ViewRenderer;
use App\Localization\Translator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SystemUserActivationViewTest extends TestCase
{
    #[DataProvider('localeProvider')]
    public function testActivationConfirmationAndInactiveDetailUseLocalizedLifecycleUi(string $locale, string $title, string $activateLabel): void
    {
        $translator = new Translator(dirname(__DIR__, 4) . '/resources/lang', $locale);
        $renderer = new ViewRenderer(dirname(__DIR__, 4) . '/resources/views', $translator);
        $user = [
            'id' => 7,
            'name' => 'Inactive & User',
            'email' => 'inactive@example.test',
            'role' => 'USER',
            'status' => 'inactive',
            'last_login_at' => null,
            'created_at' => '2026-09-29 12:00:00',
            'updated_at' => '2026-09-29 12:01:00',
        ];

        $activation = $renderer->renderPage('system-users/activate', [
            'pageTitleKey' => 'system_users.activate_title',
            'currentPath' => '/system-users/7/activate',
            'activeNav' => 'system-users',
            'systemUser' => $user,
        ]);
        $detail = $renderer->renderPage('system-users/show', [
            'pageTitleKey' => 'system_users.detail_title',
            'currentPath' => '/system-users/7',
            'activeNav' => 'system-users',
            'systemUser' => $user,
        ]);

        self::assertStringContainsString($title, $activation);
        self::assertStringContainsString($activateLabel, $activation);
        self::assertStringContainsString('Inactive &amp; User', $activation);
        self::assertStringContainsString('href="/system-users/7/activate"', $detail);
        self::assertStringNotContainsString('href="/system-users/7/edit"', $detail);
        self::assertStringNotContainsString('href="/system-users/7/deactivate"', $detail);
    }

    /** @return array<string, array{string, string, string}> */
    public static function localeProvider(): array
    {
        return [
            'en' => ['en', 'Activate system user', 'Activate'],
            'ja' => ['ja', 'システムユーザーを有効化', '有効化'],
        ];
    }
}
