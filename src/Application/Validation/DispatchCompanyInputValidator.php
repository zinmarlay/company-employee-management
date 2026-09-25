<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DispatchCompanyInput;

final class DispatchCompanyInputValidator
{
    /** @param array<string, mixed> $rawInput */
    public function validate(array $rawInput): DispatchCompanyValidationResult
    {
        $values = [];
        $errors = [];
        $code = $this->requiredString($rawInput, 'code', 30, $values, $errors);
        $name = $this->requiredString($rawInput, 'name', 160, $values, $errors);
        $phone = $this->optionalString($rawInput, 'phone', 32, $values, $errors);
        $email = $this->optionalString($rawInput, 'email', 254, $values, $errors);
        $address = $this->optionalString($rawInput, 'address', 500, $values, $errors);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $input = null;

        if ($errors === []) {
            $input = new DispatchCompanyInput($code, $name, $phone, $email, $address);
        }

        return new DispatchCompanyValidationResult($values, $input, $errors);
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $values @param array<string, string> $errors */
    private function requiredString(array $input, string $field, int $maxLength, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        $values[$field] = is_scalar($raw) ? trim((string) $raw) : '';

        if (!is_string($raw) || trim($raw) === '') {
            $errors[$field] = 'This field is required.';
            return null;
        }

        $value = trim($raw);
        if ($this->length($value) > $maxLength) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $maxLength);
            return null;
        }

        return $value;
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $values @param array<string, string> $errors */
    private function optionalString(array $input, string $field, int $maxLength, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        if ($raw === null || $raw === '') {
            $values[$field] = '';
            return null;
        }

        if (!is_string($raw)) {
            $values[$field] = is_scalar($raw) ? (string) $raw : '';
            $errors[$field] = 'Enter a text value.';
            return null;
        }

        $value = trim($raw);
        $values[$field] = $value;
        if ($value === '') {
            return null;
        }

        if ($this->length($value) > $maxLength) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $maxLength);
            return null;
        }

        return $value;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
