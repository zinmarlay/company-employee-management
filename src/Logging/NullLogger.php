<?php

declare(strict_types=1);

namespace App\Logging;

final class NullLogger implements LoggerInterface
{
    public function warning(string $event, array $context = []): void
    {
    }

    public function error(string $event, array $context = []): void
    {
    }
}
