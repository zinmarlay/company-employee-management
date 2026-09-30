<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Application\DTO\SystemUserInput;
use App\Application\Support\Clock;
use App\Application\SystemUser\SystemUserService;
use App\Application\Validation\SystemUserInputValidator;
use App\Bootstrap\Configuration;
use App\Database\ConnectionFactory;
use App\Database\DatabaseConfiguration;
use App\Database\LazyPdoConnection;
use App\Database\Migration\MigrationDiscovery;
use App\Database\Migration\MigrationRunner;
use App\Domain\SystemUser\SystemUserAlreadyActiveException;
use App\Domain\SystemUser\SystemUserLastAdminException;
use App\Domain\SystemUser\SystemUserSelfProtectionException;
use App\Infrastructure\Persistence\PdoSystemUserRepository;
use App\Http\Request;
use App\Security\AuthenticationService;
use App\Security\CsrfTokenManager;
use App\Security\SessionManager;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class SystemUserRepositoryIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?PdoSystemUserRepository $users = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('APP_ENV') !== 'test') {
            self::markTestSkipped('Set APP_ENV=test to enable database integration tests.');
        }

        foreach (['DB_TEST_HOST', 'DB_TEST_PORT', 'DB_TEST_DATABASE', 'DB_TEST_USERNAME', 'DB_TEST_PASSWORD', 'DB_TEST_CHARSET'] as $key) {
            if (getenv($key) === false) self::markTestSkipped(sprintf('%s is not configured.', $key));
        }

        $database = (string) getenv('DB_TEST_DATABASE');
        if (!str_ends_with($database, '_test')) self::fail('DB_TEST_DATABASE must end with _test.');

        try {
            $this->pdo = (new ConnectionFactory(DatabaseConfiguration::fromEnvironment([
                'DB_HOST' => getenv('DB_TEST_HOST'),
                'DB_PORT' => getenv('DB_TEST_PORT'),
                'DB_DATABASE' => $database,
                'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
                'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
                'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
            ])))->create();
        } catch (\Throwable) {
            self::markTestSkipped('Configured MySQL test database is unavailable.');
        }

        $this->resetSchema();
        self::assertSame(8, (new MigrationRunner($this->pdo, new MigrationDiscovery(dirname(__DIR__, 3) . '/database/migrations')))->migrate());
        $configuration = Configuration::fromEnvironment(dirname(__DIR__, 3) . '/config/app.php', [
            'APP_ENV' => 'test',
            'DB_HOST' => getenv('DB_TEST_HOST'),
            'DB_PORT' => getenv('DB_TEST_PORT'),
            'DB_DATABASE' => $database,
            'DB_USERNAME' => getenv('DB_TEST_USERNAME'),
            'DB_PASSWORD' => getenv('DB_TEST_PASSWORD'),
            'DB_CHARSET' => getenv('DB_TEST_CHARSET'),
        ]);
        $this->users = new PdoSystemUserRepository(new LazyPdoConnection($configuration));
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) $this->resetSchema();
        parent::tearDown();
    }

    public function testSystemUsersPersistSafeRecordsAndProtectTheLastActiveAdmin(): void
    {
        $service = new SystemUserService($this->users(), new SystemUserInputValidator(), new FixedSystemUserClock());
        $adminOne = $this->create($service, 'Admin One', 'admin-one@example.test', 'ADMIN');
        $adminTwo = $this->create($service, 'Admin Two', 'admin-two@example.test', 'ADMIN');

        $safe = $this->users()->findSafeById($adminOne);
        $authentication = $this->users()->findByEmailForAuthentication('admin-one@example.test');

        self::assertNotNull($safe);
        self::assertArrayNotHasKey('password_hash', $safe);
        self::assertNotNull($authentication);
        self::assertNotSame('long-enough-password', $authentication['password_hash']);
        self::assertSame(2, count($this->users()->list()));

        try {
            $this->users()->deactivate($adminOne, $adminOne, '2026-09-29 11:59:00');
            self::fail('Expected self-deactivation to be rejected.');
        } catch (SystemUserSelfProtectionException) {
            self::assertSame('active', $this->users()->findSafeById($adminOne)['status']);
        }

        $this->users()->deactivate($adminOne, $adminTwo, '2026-09-29 12:00:00');
        self::assertSame('inactive', $this->users()->findSafeById($adminOne)['status']);

        $this->expectException(SystemUserLastAdminException::class);
        $this->users()->deactivate($adminTwo, $adminOne, '2026-09-29 12:01:00');
    }

    public function testInactiveUserCanBeReactivatedWithoutChangingIdentityRolePasswordOrHistory(): void
    {
        $service = new SystemUserService($this->users(), new SystemUserInputValidator(), new FixedSystemUserClock());
        $adminId = $this->create($service, 'Admin One', 'admin-one@example.test', 'ADMIN');
        $userId = $this->create($service, 'Read Only User', 'user@example.test', 'USER');
        $before = $this->users()->findById($userId);

        self::assertNotNull($before);
        $this->users()->deactivate($userId, $adminId, '2026-09-29 12:01:00');
        self::assertSame('inactive', $this->users()->findSafeById($userId)['status']);

        $this->users()->activate($userId, '2026-09-29 12:02:00');
        $after = $this->users()->findById($userId);

        self::assertNotNull($after);
        self::assertSame('active', $after['status']);
        self::assertSame($before['name'], $after['name']);
        self::assertSame($before['email'], $after['email']);
        self::assertSame($before['role'], $after['role']);
        self::assertSame($before['password_hash'], $after['password_hash']);
        self::assertSame($before['created_at'], $after['created_at']);
        self::assertSame('2026-09-29 12:02:00', $after['updated_at']);

        $this->expectException(SystemUserAlreadyActiveException::class);
        $this->users()->activate($userId, '2026-09-29 12:03:00');
    }

    public function testCompleteUserLifecycleRestoresAuthenticationWithTheOriginalPassword(): void
    {
        $password = 'same-original-password';
        $service = new SystemUserService($this->users(), new SystemUserInputValidator(), new FixedSystemUserClock());
        $adminId = $this->create($service, 'Admin One', 'admin-one@example.test', 'ADMIN');
        $userId = $this->create($service, 'Read Only User', 'user@example.test', 'USER', $password);
        $before = $this->users()->findById($userId);
        self::assertNotNull($before);
        $originalHash = (string) $before['password_hash'];
        self::assertTrue(password_verify($password, $originalHash));

        $session = new SessionManager();
        $session->start(Request::fromValues('GET', '/'));
        $authentication = new AuthenticationService(
            $this->users(),
            $session,
            new CsrfTokenManager($session),
            new FixedSystemUserClock(),
        );

        try {
            self::assertNotNull($authentication->authenticate('USER@EXAMPLE.TEST', $password));

            $this->users()->deactivate($userId, $adminId, '2026-09-29 12:01:00');
            $session->clearAuthentication();
            self::assertNull($authentication->authenticate('user@example.test', $password));

            $this->users()->activate($userId, '2026-09-29 12:02:00');
            $after = $this->users()->findById($userId);

            self::assertNotNull($after);
            self::assertSame($userId, (int) $after['id']);
            self::assertSame('user@example.test', $after['email']);
            self::assertSame('USER', $after['role']);
            self::assertSame('active', $after['status']);
            self::assertSame($originalHash, $after['password_hash']);
            self::assertTrue(password_verify($password, (string) $after['password_hash']));
            self::assertNotNull($authentication->authenticate('user@example.test', $password));
        } finally {
            $session->destroy();
        }
    }

    private function create(SystemUserService $service, string $name, string $email, string $role, string $password = 'long-enough-password'): int
    {
        $result = $service->validateCreate([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
        self::assertTrue($result->isValid());

        return $service->create($result->input);
    }

    private function users(): PdoSystemUserRepository
    {
        return $this->users ?? throw new \LogicException('Repository is not initialized.');
    }

    private function resetSchema(): void
    {
        foreach (['system_users', 'employee_certifications', 'employee_projects', 'employee_skills', 'skills', 'dispatch_contracts', 'dispatch_companies', 'employee_code_sequences', 'employees', 'departments', 'branches', 'companies', 'schema_migrations'] as $table) {
            $this->pdo?->exec('DROP TABLE IF EXISTS ' . $table);
        }
    }
}

final class FixedSystemUserClock implements Clock
{
    public function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-29 12:00:00', new DateTimeZone('UTC'));
    }
}
