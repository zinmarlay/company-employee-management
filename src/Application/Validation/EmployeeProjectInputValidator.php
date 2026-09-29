<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\EmployeeProjectInput;
use DateTimeImmutable;

final class EmployeeProjectInputValidator
{
    /** @param array<string,mixed> $rawInput */
    public function validate(array $rawInput): EmployeeProjectValidationResult
    {
        $values = [];
        $errors = [];
        $name = $this->required($rawInput, 'project_name', 160, $values, $errors);
        $role = $this->required($rawInput, 'role', 120, $values, $errors);
        $start = $this->required($rawInput, 'start_date', 10, $values, $errors);
        $end = $this->optionalDate($rawInput, 'end_date', $values, $errors);
        $description = $this->optional($rawInput, 'description', 5000, $values, $errors);
        $responsibilities = $this->optional($rawInput, 'responsibilities', 5000, $values, $errors);
        $technologies = $this->optional($rawInput, 'technologies', 2000, $values, $errors);

        if ($start !== null && !$this->isDate($start)) {
            $errors['start_date'] = 'Enter a valid date in YYYY-MM-DD format.';
        }
        if ($end !== null && !$this->isDate($end)) {
            $errors['end_date'] = 'Enter a valid date in YYYY-MM-DD format.';
        }
        if ($start !== null && $end !== null && $this->isDate($start) && $this->isDate($end) && $start > $end) {
            $errors['start_date'] = 'Start date must be on or before end date.';
            $errors['end_date'] = 'End date must be on or after start date.';
        }

        $input = null;
        if ($errors === []) {
            $input = new EmployeeProjectInput($name, $role, $start, $end, $description, $responsibilities, $technologies);
        }

        return new EmployeeProjectValidationResult($values, $input, $errors);
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

