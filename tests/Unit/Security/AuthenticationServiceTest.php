<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Application\DTO\SystemUserInput;
use App\Application\Support\Clock;
use App\Application\SystemUser\SystemUserService;
use App\Application\Validation\SystemUserInputValidator;
use App\Domain\SystemUser\SystemUserRepositoryInterface;
use App\Security\AuthenticationService;
use App\Security\CsrfTokenManager;
use App\Security\SessionManager;
use App\Http\Request;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class AuthenticationServiceTest extends TestCase
{
    private ?SessionManager $session = null;

    protected function tearDown(): void
    {
        if ($this->session instanceof SessionManager) {
            $this->session->destroy();
        }

        parent::tearDown();
    }

    public function testReactivatedUserCanAuthenticateAgain(): void
    {
        $passwordHash = password_hash('long-enough-password', PASSWORD_DEFAULT);
        $repository = new AuthenticationTestRepository([
            'id' => 7,
            'name' => 'Read Only User',
            'email' => 'user@example.test',
            'password_hash' => $passwordHash,
            'role' => 'USER',
            'status' => 'inactive',
            'last_login_at' => null,
        ]);
        $this->session = new SessionManager();
        $this->session->start(Request::fromValues('GET', '/'));
        $authentication = new AuthenticationService(
            $repository,
            $this->session,
            new CsrfTokenManager($this->session),
            new AuthenticationTestClock(),
        );
        $systemUsers = new SystemUserService($repository, new SystemUserInputValidator(), new AuthenticationTestClock());

        self::assertNull($authentication->authenticate('user@example.test', 'long-enough-password'));
        $systemUsers->activate(7);

        $user = $authentication->authenticate('user@example.test', 'long-enough-password');

        self::assertNotNull($user);
        self::assertSame(7, $user->id);
        self::assertSame('USER', $user->role);
    }
}

final class AuthenticationTestRepository implements SystemUserRepositoryInterface
{
    /** @param array<string, mixed> $record */
    public function __construct(public array $record)
    {
    }

    public function list(): array { return [$this->record]; }
    public function findById(int $id): ?array { return $id === (int) $this->record['id'] ? $this->record : null; }
    public function findByEmailForAuthentication(string $email): ?array { return $email === $this->record['email'] ? $this->record : null; }
    public function findSafeById(int $id): ?array { return $this->findById($id); }
    public function emailExists(string $email, ?int $exceptId = null): bool { return false; }
    public function insert(SystemUserInput $input, string $passwordHash, string $timestamp): int { throw new \LogicException(); }
    public function update(int $id, SystemUserInput $input, ?string $passwordHash, string $timestamp, int $actingUserId): void { throw new \LogicException(); }
    public function deactivate(int $id, int $actingUserId, string $timestamp): void { throw new \LogicException(); }
    public function activate(int $id, string $timestamp): void { $this->record['status'] = 'active'; }
    public function recordSuccessfulLogin(int $id, string $timestamp, ?string $passwordHash): void
    {
        $this->record['last_login_at'] = $timestamp;
        if ($passwordHash !== null) {
            $this->record['password_hash'] = $passwordHash;
        }
    }
}

final class AuthenticationTestClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-29 12:00:00', new DateTimeZone('UTC'));
    }
}
