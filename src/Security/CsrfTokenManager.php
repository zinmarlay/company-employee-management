<?php

declare(strict_types=1);

namespace App\Security;

final class CsrfTokenManager
{
    public function __construct(private readonly SessionManager $session)
    {
    }

    public function token(): string
    {
        $existing = $this->session->csrfToken();

        if ($existing !== null) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $this->session->setCsrfToken($token);

        return $token;
    }

    public function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->setCsrfToken($token);

        return $token;
    }

    public function clear(): void
    {
        $this->session->clearCsrfToken();
    }

    public function isValid(mixed $submitted): bool
    {
        $stored = $this->session->csrfToken();

        return is_string($submitted)
            && $stored !== null
            && $submitted !== ''
            && hash_equals($stored, $submitted);
    }
}
