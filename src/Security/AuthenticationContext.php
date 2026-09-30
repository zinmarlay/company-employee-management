<?php

declare(strict_types=1);

namespace App\Security;

final class AuthenticationContext
{
    private ?AuthenticatedUser $user = null;

    public function set(?AuthenticatedUser $user): void
    {
        $this->user = $user;
    }

    public function user(): ?AuthenticatedUser
    {
        return $this->user;
    }
}
