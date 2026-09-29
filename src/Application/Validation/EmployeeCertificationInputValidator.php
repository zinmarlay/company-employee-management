<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\EmployeeCertificationInput;
use DateTimeImmutable;

final class EmployeeCertificationInputValidator
{
    /** @param array<string,mixed> $rawInput */
    public function validate(array $rawInput): EmployeeCertificationValidationResult
    {
        $values = [];
        $errors = [];
        $name = $this->required($rawInput, 'certification_name', 160, $values, $errors);
        $issuer = $this->required($rawInput, 'issuing_organization', 120, $values, $errors);
        $obtained = $this->required($rawInput, 'obtained_date', 10, $values, $errors);
        $expiration = $this->optionalDate($rawInput, 'expiration_date', $values, $errors);
        $credential = $this->optional($rawInput, 'credential_identifier', 120, $values, $errors);
        $notes = $this->optional($rawInput, 'notes', 5000, $values, $errors);

        if ($obtained !== null && !$this->isDate($obtained)) {
            $errors['obtained_date'] = 'Enter a valid date in YYYY-MM-DD format.';
        }
        if ($expiration !== null && !$this->isDate($expiration)) {
            $errors['expiration_date'] = 'Enter a valid date in YYYY-MM-DD format.';
        }
        if ($obtained !== null && $expiration !== null && $this->isDate($obtained) && $this->isDate($expiration) && $expiration < $obtained) {
            $errors['obtained_date'] = 'Obtained date must be on or before expiration date.';
            $errors['expiration_date'] = 'Expiration date must be on or after obtained date.';
        }

        $input = null;
        if ($errors === []) {
            $input = new EmployeeCertificationInput($name, $issuer, $obtained, $expiration, $credential, $notes);
        }

        return new EmployeeCertificationValidationResult($values, $input, $errors);
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $values @param array<string,string> $errors */
    private function required(array $input, string $field, int $max, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        if (!is_string($raw)) {
            $values[$field] = is_scalar($raw) ? (string) $raw : '';
            $errors[$field] = 'This field is required.';
            return null;
        }
        $value = trim($raw);
        $values[$field] = $value;
        if ($value === '') {
            $errors[$field] = 'This field is required.';
            return null;
        }
        if ($this->length($value) > $max) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $max);
            return null;
        }
        return $value;
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $values @param array<string,string> $errors */
    private function optional(array $input, string $field, int $max, array &$values, array &$errors): ?string
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
        if ($value === '') return null;
        if ($this->length($value) > $max) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $max);
            return null;
        }
        return $value;
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $values @param array<string,string> $errors */
    private function optionalDate(array $input, string $field, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        if ($raw === null || $raw === '') {
            $values[$field] = '';
            return null;
        }
        if (!is_string($raw)) {
            $values[$field] = is_scalar($raw) ? (string) $raw : '';
            $errors[$field] = 'Enter a valid date in YYYY-MM-DD format.';
            return null;
        }
        $value = trim($raw);
        $values[$field] = $value;
        return $value;
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

