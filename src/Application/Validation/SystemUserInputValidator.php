<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\SystemUserInput;

final class SystemUserInputValidator
{
    /**
     * @param array<string, mixed> $rawInput
     */
    public function validate(array $rawInput, bool $creating): SystemUserValidationResult
    {
        $values = [];
        $errors = [];
        $name = $this->requiredString($rawInput, 'name', 120, $values, $errors);
        $email = $this->requiredString($rawInput, 'email', 254, $values, $errors);
        $role = $this->requiredString($rawInput, 'role', 20, $values, $errors);
        $password = null;

        if ($email !== null) {
            $email = strtolower($email);
            $values['email'] = $email;
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Enter a valid email address.';
            }
        }

        if ($role !== null && !in_array($role, ['ADMIN', 'USER'], true)) {
            $errors['role'] = 'Select a valid role.';
        }

        $rawPassword = $rawInput['password'] ?? '';
        $rawConfirmation = $rawInput['password_confirmation'] ?? '';
        $passwordProvided = !is_string($rawPassword) || $rawPassword !== '';
        $confirmationProvided = !is_string($rawConfirmation) || $rawConfirmation !== '';

        if ($creating || $passwordProvided || $confirmationProvided) {
            if (!is_string($rawPassword) || $rawPassword === '') {
                $errors['password'] = 'Enter a password.';
            } elseif (strlen($rawPassword) > 128) {
                $errors['password'] = 'Password must be 128 bytes or fewer.';
            } elseif (mb_strlen($rawPassword) < 12) {
                $errors['password'] = 'Password must be at least 12 characters.';
            }

            if (!is_string($rawConfirmation) || $rawPassword !== $rawConfirmation) {
                $errors['password_confirmation'] = 'Password confirmation does not match.';
            }

            if ($errors['password'] ?? null) {
                // The password is intentionally never returned in values.
            } elseif ($errors['password_confirmation'] ?? null) {
                // The password is intentionally never returned in values.
            } else {
                $password = $rawPassword;
            }
        }

        if ($errors !== []) {
            return new SystemUserValidationResult($values, null, $errors);
        }

        return new SystemUserValidationResult(
            $values,
            new SystemUserInput($name ?? '', $email ?? '', $role ?? '', $password),
            [],
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function requiredString(array $input, string $field, int $maxLength, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        $value = is_string($raw) ? trim($raw) : '';
        $values[$field] = $value;

        if ($value === '') {
            $errors[$field] = 'This field is required.';

            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $maxLength);

            return null;
        }

        return $value;
    }
}
