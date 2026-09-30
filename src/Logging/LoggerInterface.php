<?php

declare(strict_types=1);

namespace App\Logging;

interface LoggerInterface
{
    /** @param array<string, scalar|null> $context */
    public function warning(string $event, array $context = []): void;

    /** @param array<string, scalar|null> $context */
    public function error(string $event, array $context = []): void;
}
