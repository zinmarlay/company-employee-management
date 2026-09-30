<?php

declare(strict_types=1);

namespace App\Application\SystemUser;

use App\Application\DTO\SystemUserInput;
use App\Application\Support\Clock;
use App\Application\Validation\SystemUserInputValidator;
use App\Application\Validation\SystemUserValidationResult;
use App\Domain\SystemUser\SystemUserRepositoryInterface;

final class SystemUserService
{
    public function __construct(
        private readonly SystemUserRepositoryInterface $users,
        private readonly SystemUserInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @param array<string, mixed> $rawInput */
    public function validateCreate(array $rawInput): SystemUserValidationResult
    {
        $result = $this->validator->validate($rawInput, true);

        return $this->withEmailConflict($result, null);
    }

    /** @param array<string, mixed> $rawInput */
    public function validateUpdate(array $rawInput, int $id): SystemUserValidationResult
    {
        $result = $this->validator->validate($rawInput, false);

        return $this->withEmailConflict($result, $id);
    }

    public function create(SystemUserInput $input): int
    {
        $passwordHash = password_hash((string) $input->password, PASSWORD_DEFAULT);

        return $this->users->insert($input, $passwordHash, $this->timestamp());
    }

    public function update(int $id, SystemUserInput $input, int $actingUserId): void
    {
        $passwordHash = $input->password === null ? null : password_hash($input->password, PASSWORD_DEFAULT);
        $this->users->update($id, $input, $passwordHash, $this->timestamp(), $actingUserId);
    }

    public function deactivate(int $id, int $actingUserId): void
    {
        $this->users->deactivate($id, $actingUserId, $this->timestamp());
    }

    public function activate(int $id): void
    {
        $this->users->activate($id, $this->timestamp());
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->users->list();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->users->findSafeById($id);
    }

    private function timestamp(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }

    private function withEmailConflict(SystemUserValidationResult $result, ?int $exceptId): SystemUserValidationResult
    {
        if (!$result->isValid() || $this->users->emailExists($result->input->email, $exceptId) === false) {
            return $result;
        }

        return new SystemUserValidationResult(
            $result->values,
            null,
            [...$result->errors, 'email' => 'A system user with this email already exists.'],
        );
    }
}
