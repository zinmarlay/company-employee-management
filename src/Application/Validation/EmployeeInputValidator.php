<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\EmployeeInput;
use DateTimeImmutable;

final class EmployeeInputValidator
{
    /**
     * @param array<string, mixed> $rawInput
     */
    public function validate(array $rawInput): EmployeeValidationResult
    {
        $values = [];
        $errors = [];

        $employeeCode = $this->requiredString($rawInput, 'employee_code', 40, $values, $errors);
        $firstName = $this->requiredString($rawInput, 'first_name', 100, $values, $errors);
        $lastName = $this->requiredString($rawInput, 'last_name', 100, $values, $errors);
        $firstNameKana = $this->requiredString($rawInput, 'first_name_kana', 100, $values, $errors);
        $lastNameKana = $this->requiredString($rawInput, 'last_name_kana', 100, $values, $errors);
        $email = $this->requiredString($rawInput, 'email', 254, $values, $errors);
        $phone = $this->optionalString($rawInput, 'phone', 32, $values, $errors);
        $positionTitle = $this->optionalString($rawInput, 'position_title', 120, $values, $errors);
        $branchId = $this->requiredPositiveInteger($rawInput, 'branch_id', $values, $errors);
        $departmentId = $this->optionalPositiveInteger($rawInput, 'department_id', $values, $errors);
        $employeeType = $this->requiredString($rawInput, 'employee_type', 20, $values, $errors);
        $hireDate = $this->requiredString($rawInput, 'hire_date', 10, $values, $errors);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($employeeType !== null && !in_array($employeeType, ['permanent', 'dispatched'], true)) {
            $errors['employee_type'] = 'Select a valid employee type.';
        }

        if ($hireDate !== null && !$this->isDate($hireDate)) {
            $errors['hire_date'] = 'Enter a valid date in YYYY-MM-DD format.';
        }

        $input = null;

        if ($errors === []) {
            $input = new EmployeeInput(
                $employeeCode,
                $firstName,
                $lastName,
                $firstNameKana,
                $lastNameKana,
                $email,
                $phone,
                $positionTitle,
                $branchId,
                $departmentId,
                $employeeType,
                $hireDate,
            );
        }

        return new EmployeeValidationResult($values, $input, $errors);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function requiredString(
        array $input,
        string $field,
        int $maxLength,
        array &$values,
        array &$errors,
    ): ?string {
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

        if ($this->length($value) > $maxLength) {
            $errors[$field] = sprintf('This field must be %d characters or fewer.', $maxLength);

            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function optionalString(
        array $input,
        string $field,
        int $maxLength,
        array &$values,
        array &$errors,
    ): ?string {
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

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function requiredPositiveInteger(
        array $input,
        string $field,
        array &$values,
        array &$errors,
    ): ?int {
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

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function optionalPositiveInteger(
        array $input,
        string $field,
        array &$values,
        array &$errors,
    ): ?int {
        $raw = $input[$field] ?? null;

        if ($raw === null || $raw === '') {
            $values[$field] = '';

            return null;
        }

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

        $integer = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return is_int($integer) ? $integer : null;
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
