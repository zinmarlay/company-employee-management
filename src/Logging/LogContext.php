<?php

declare(strict_types=1);

namespace App\Logging;

use App\Http\Request;

final class LogContext
{
    /** @var array<int, string> */
    private const ALLOWED = [
        'request_id', 'method', 'path', 'outcome', 'status', 'exception_class', 'reason',
    ];

    /** @param array<string, scalar|null> $additional @return array<string, scalar|null> */
    public static function request(Request $request, array $additional = []): array
    {
        $context = [
            'request_id' => $request->requestId(),
            'method' => $request->method(),
            'path' => $request->path(),
        ];

        foreach ($additional as $key => $value) {
            if (in_array($key, self::ALLOWED, true) && (is_scalar($value) || $value === null)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /** @param array<string, scalar|null> $context @return array<string, scalar|null> */
    public static function sanitize(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            if (in_array($key, self::ALLOWED, true) && (is_scalar($value) || $value === null)) {
                $safe[$key] = $value;
            }
        }
        return $safe;
    }
}
