<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\SystemUserInput;
use App\Database\LazyPdoConnection;
use App\Domain\SystemUser\SystemUserDuplicateException;
use App\Domain\SystemUser\SystemUserAlreadyActiveException;
use App\Domain\SystemUser\SystemUserInactiveException;
use App\Domain\SystemUser\SystemUserLastAdminException;
use App\Domain\SystemUser\SystemUserNotFoundException;
use App\Domain\SystemUser\SystemUserRepositoryInterface;
use App\Domain\SystemUser\SystemUserSelfProtectionException;
use PDO;
use PDOException;
use Throwable;

final class PdoSystemUserRepository implements SystemUserRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function list(): array
    {
        $statement = $this->connection->get()->query(
            'SELECT id, name, email, role, status, last_login_at, created_at, updated_at '
            . 'FROM system_users ORDER BY status ASC, name ASC, id ASC',
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, name, email, password_hash, role, status, last_login_at, created_at, updated_at '
            . 'FROM system_users WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findByEmailForAuthentication(string $email): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, name, email, password_hash, role, status, last_login_at '
            . 'FROM system_users WHERE email = :email',
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findSafeById(int $id): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, name, email, role, status, last_login_at, created_at, updated_at '
            . 'FROM system_users WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM system_users WHERE email = :email';
        $parameters = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $statement = $this->connection->get()->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function insert(SystemUserInput $input, string $passwordHash, string $timestamp): int
    {
        try {
            $statement = $this->connection->get()->prepare(
                'INSERT INTO system_users '
                . '(name, email, password_hash, role, status, created_at, updated_at) '
                . 'VALUES (:name, :email, :password_hash, :role, \'active\', :created_at, :updated_at)',
            );
            $statement->execute([
                'name' => $input->name,
                'email' => $input->email,
                'password_hash' => $passwordHash,
                'role' => $input->role,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            return (int) $this->connection->get()->lastInsertId();
        } catch (PDOException $exception) {
            if ($this->isDuplicate($exception)) {
                throw new SystemUserDuplicateException('The system-user email is already in use.', 0, $exception);
            }

            throw $exception;
        }
    }

    public function update(
        int $id,
        SystemUserInput $input,
        ?string $passwordHash,
        string $timestamp,
        int $actingUserId,
    ): void {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $this->lockActiveAdmins($pdo);
            $target = $this->lockedUser($pdo, $id);

            if ($target === null) {
                throw new SystemUserNotFoundException('System user was not found.');
            }

            $this->assertProtectedMutation($target, $input->role, $target['status'] === 'inactive', $actingUserId);

            $sets = [
                'name = :name',
                'email = :email',
                'role = :role',
                'updated_at = :updated_at',
            ];
            $parameters = [
                'id' => $id,
                'name' => $input->name,
                'email' => $input->email,
                'role' => $input->role,
                'updated_at' => $timestamp,
            ];

            if ($passwordHash !== null) {
                $sets[] = 'password_hash = :password_hash';
                $parameters['password_hash'] = $passwordHash;
            }

            try {
                $statement = $pdo->prepare('UPDATE system_users SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $statement->execute($parameters);
            } catch (PDOException $exception) {
                if ($this->isDuplicate($exception)) {
                    throw new SystemUserDuplicateException('The system-user email is already in use.', 0, $exception);
                }

                throw $exception;
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function deactivate(int $id, int $actingUserId, string $timestamp): void
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $this->lockActiveAdmins($pdo);
            $target = $this->lockedUser($pdo, $id);

            if ($target === null) {
                throw new SystemUserNotFoundException('System user was not found.');
            }

            if ($target['status'] === 'inactive') {
                throw new SystemUserInactiveException('The system user is already inactive.');
            }

            $this->assertProtectedMutation($target, $target['role'], true, $actingUserId);
            $statement = $pdo->prepare(
                "UPDATE system_users SET status = 'inactive', updated_at = :updated_at WHERE id = :id",
            );
            $statement->execute(['id' => $id, 'updated_at' => $timestamp]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function activate(int $id, string $timestamp): void
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            // Use the same lock order as deactivation so concurrent lifecycle
            // changes cannot observe an inconsistent ADMIN state.
            $this->lockActiveAdmins($pdo);
            $target = $this->lockedUser($pdo, $id);

            if ($target === null) {
                throw new SystemUserNotFoundException('System user was not found.');
            }

            if ($target['status'] === 'active') {
                throw new SystemUserAlreadyActiveException('The system user is already active.');
            }

            $statement = $pdo->prepare(
                "UPDATE system_users SET status = 'active', updated_at = :updated_at WHERE id = :id AND status = 'inactive'",
            );
            $statement->execute(['id' => $id, 'updated_at' => $timestamp]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function recordSuccessfulLogin(int $id, string $timestamp, ?string $passwordHash): void
    {
        $sets = ['last_login_at = :last_login_at', 'updated_at = :updated_at'];
        $parameters = ['id' => $id, 'last_login_at' => $timestamp, 'updated_at' => $timestamp];

        if ($passwordHash !== null) {
            $sets[] = 'password_hash = :password_hash';
            $parameters['password_hash'] = $passwordHash;
        }

        $statement = $this->connection->get()->prepare(
            'UPDATE system_users SET ' . implode(', ', $sets) . ' WHERE id = :id AND status = \'active\'',
        );
        $statement->execute($parameters);
    }

    private function lockActiveAdmins(PDO $pdo): void
    {
        $statement = $pdo->query(
            "SELECT id FROM system_users WHERE status = 'active' AND role = 'ADMIN' ORDER BY id ASC FOR UPDATE",
        );
        $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    private function lockedUser(PDO $pdo, int $id): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, name, email, role, status, password_hash FROM system_users WHERE id = :id FOR UPDATE',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $target */
    private function assertProtectedMutation(array $target, string $newRole, bool $deactivating, int $actingUserId): void
    {
        if ((int) $target['id'] === $actingUserId
            && ($deactivating || ($target['role'] === 'ADMIN' && $newRole !== 'ADMIN'))
        ) {
            throw new SystemUserSelfProtectionException('The current system user cannot remove their own access.');
        }

        if ($target['status'] === 'active' && $target['role'] === 'ADMIN'
            && ($deactivating || $newRole !== 'ADMIN')
        ) {
            $pdo = $this->connection->get();
            $count = (int) $pdo->query(
                "SELECT COUNT(*) FROM system_users WHERE status = 'active' AND role = 'ADMIN'",
            )->fetchColumn();

            if ($count <= 1) {
                throw new SystemUserLastAdminException('At least one active administrator is required.');
            }
        }
    }

    private function isDuplicate(PDOException $exception): bool
    {
        return $exception->getCode() === '23000' || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
