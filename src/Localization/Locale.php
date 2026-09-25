<?php

declare(strict_types=1);

namespace App\Localization;

final class Locale
{
    public const ENGLISH = 'en';
    public const JAPANESE = 'ja';

    /** @return array<int, string> */
    public static function supported(): array
    {
        return [self::ENGLISH, self::JAPANESE];
    }

    public static function normalize(mixed $locale): ?string
    {
        if (!is_string($locale)) {
            return null;
        }

        $locale = strtolower(trim($locale));

        return in_array($locale, self::supported(), true) ? $locale : null;
    }
}
