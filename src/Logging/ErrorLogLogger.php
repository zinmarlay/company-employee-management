<?php

declare(strict_types=1);

namespace App\Logging;

final class ErrorLogLogger implements LoggerInterface
{
    public function warning(string $event, array $context = []): void
    {
        $this->write('warning', $event, $context);
    }

    public function error(string $event, array $context = []): void
    {
        $this->write('error', $event, $context);
    }

    /** @param array<string, scalar|null> $context */
    private function write(string $level, string $event, array $context): void
    {
        $payload = json_encode([
            'level' => $level,
            'event' => $event,
            'context' => LogContext::sanitize($context),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        error_log(is_string($payload) ? $payload : $level . ' ' . $event);
    }
}
