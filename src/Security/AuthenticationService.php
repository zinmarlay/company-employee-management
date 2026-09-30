<?php

declare(strict_types=1);

namespace App\Security;

use App\Application\Support\Clock;
use App\Domain\SystemUser\SystemUserRepositoryInterface;

final class AuthenticationService
{
    private readonly string $dummyHash;

    public function __construct(
        private readonly SystemUserRepositoryInterface $users,
        private readonly SessionManager $session,
        private readonly CsrfTokenManager $csrf,
        private readonly Clock $clock,
    ) {
        $this->dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    }

    public function authenticate(string $email, string $password): ?AuthenticatedUser
    {
        $normalizedEmail = strtolower(trim($email));
        $record = $this->users->findByEmailForAuthentication($normalizedEmail);
        $hash = $record !== null && $record['status'] === 'active'
            ? (string) $record['password_hash']
            : $this->dummyHash;
        $valid = password_verify($password, $hash);

        if (!$valid || $record === null || $record['status'] !== 'active') {
            return null;
        }

        $replacementHash = password_needs_rehash((string) $record['password_hash'], PASSWORD_DEFAULT)
            ? password_hash($password, PASSWORD_DEFAULT)
            : null;
        $this->session->regenerate();
        $this->session->setUserId((int) $record['id']);
        $this->csrf->rotate();
        $this->users->recordSuccessfulLogin(
            (int) $record['id'],
            $this->clock->nowUtc()->format('Y-m-d H:i:s'),
            $replacementHash,
        );

        return AuthenticatedUser::fromRow($record);
    }
}
