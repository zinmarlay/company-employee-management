<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\BranchInput;

final class BranchInputValidator
{
    /** @param array<string, mixed> $rawInput */
    public function validate(array $rawInput): BranchValidationResult
    {
        $values = [];
        $errors = [];
        $companyId = $this->requiredPositiveInteger($rawInput, 'company_id', $values, $errors);
        $code = $this->requiredString($rawInput, 'code', 30, $values, $errors);
        $name = $this->requiredString($rawInput, 'name', 160, $values, $errors);
        $city = $this->requiredString($rawInput, 'city', 120, $values, $errors);
        $address = $this->requiredString($rawInput, 'address', 500, $values, $errors);
        $phone = $this->requiredString($rawInput, 'phone', 32, $values, $errors);

        $input = null;
        if ($errors === []) {
            $input = new BranchInput($companyId, $code, $name, $city, $address, $phone);
        }

        return new BranchValidationResult($values, $input, $errors);
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
    private function requiredPositiveInteger(array $input, string $field, array &$values, array &$errors): ?int
    {
        $raw = $input[$field] ?? null;
        $values[$field] = is_scalar($raw) ? (string) $raw : '';
        $value = $this->positiveInteger($raw);

        if ($value === null) {
            $errors[$field] = 'Select a valid value.';
        } else {
            $values[$field] = $value;
        }

        return $value;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', trim($value)) !== 1) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return is_int($integer) ? $integer : null;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
