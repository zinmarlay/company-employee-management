<?php

declare(strict_types=1);

namespace App\Http\View;

use InvalidArgumentException;

final class HtmlEscaper
{
    public static function escape(string|int|float|bool|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function __construct()
    {
        throw new InvalidArgumentException('HtmlEscaper is a static presentation helper.');
    }
}
