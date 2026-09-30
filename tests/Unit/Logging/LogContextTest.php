<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\LogContext;
use PHPUnit\Framework\TestCase;

final class LogContextTest extends TestCase
{
    public function testSanitizationIsAllowlistFirstAndDropsSensitiveFields(): void
    {
        $safe = LogContext::sanitize([
            'request_id' => 'abc123',
            'method' => 'POST',
            'path' => '/login',
            'outcome' => 'invalid_credentials',
            'password' => 'secret',
            'email' => 'person@example.test',
            'request_body' => ['password' => 'secret'],
        ]);

        self::assertSame([
            'request_id' => 'abc123',
            'method' => 'POST',
            'path' => '/login',
            'outcome' => 'invalid_credentials',
        ], $safe);
    }
}
