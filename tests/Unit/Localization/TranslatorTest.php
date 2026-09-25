<?php

declare(strict_types=1);

namespace Tests\Unit\Localization;

use App\Localization\Locale;
use App\Localization\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator(dirname(__DIR__, 3) . '/resources/lang');
    }

    public function testEnglishIsTheDefaultLocale(): void
    {
        self::assertSame(Locale::ENGLISH, $this->translator->locale());
        self::assertSame('Employees', $this->translator->get('navigation.employees'));
    }

    public function testJapaneseResourceCanBeSelectedAndInterpolated(): void
    {
        $this->translator->setLocale(Locale::JAPANESE);

        self::assertSame('社員', $this->translator->get('navigation.employees'));
        self::assertSame('3件', $this->translator->get('employees.record_count', ['count' => 3]));
    }

    public function testUnsupportedLocaleFallsBackToEnglish(): void
    {
        $this->translator->setLocale('fr');

        self::assertSame(Locale::ENGLISH, $this->translator->locale());
        self::assertSame('Employees', $this->translator->get('navigation.employees'));
    }

    public function testValidationMessagesUseTheActiveLocale(): void
    {
        $this->translator->setLocale(Locale::JAPANESE);

        self::assertSame('この項目は必須です。', $this->translator->validationMessage('This field is required.'));
        self::assertSame('10文字以内で入力してください。', $this->translator->validationMessage('This field must be 10 characters or fewer.'));
    }
}
