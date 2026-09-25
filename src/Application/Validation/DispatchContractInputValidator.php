<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DispatchContractInput;
use DateTimeImmutable;

final class DispatchContractInputValidator
{
    /** @param array<string, mixed> $rawInput */
    public function validate(array $rawInput): DispatchContractValidationResult
    {
        $values = [];
        $errors = [];
        $employeeId = $this->positiveInteger($rawInput['employee_id'] ?? null, 'employee_id', $values, $errors);
        $companyId = $this->positiveInteger($rawInput['dispatch_company_id'] ?? null, 'dispatch_company_id', $values, $errors);
        $startDate = $this->date($rawInput['start_date'] ?? null, 'start_date', $values, $errors);
        $endDate = $this->date($rawInput['end_date'] ?? null, 'end_date', $values, $errors);

        if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
            $errors['start_date'] = 'Start date must be on or before end date.';
            $errors['end_date'] = 'End date must be on or after start date.';
        }

        $input = null;
        if ($errors === []) {
            $input = new DispatchContractInput($employeeId, $companyId, $startDate, $endDate);
        }

        return new DispatchContractValidationResult($values, $input, $errors);
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function positiveInteger(mixed $raw, string $field, array &$values, array &$errors): ?int
    {
        $values[$field] = is_scalar($raw) ? (string) $raw : '';
        if (is_int($raw)) {
            $value = $raw > 0 ? $raw : null;
        } else {
            $value = is_string($raw) && preg_match('/^[1-9][0-9]*$/', trim($raw)) === 1
                ? filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : null;
            $value = is_int($value) ? $value : null;
        }

        if ($value === null) {
            $errors[$field] = 'Select a valid value.';
            return null;
        }

        $values[$field] = $value;
        return $value;
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function date(mixed $raw, string $field, array &$values, array &$errors): ?string
    {
        $values[$field] = is_scalar($raw) ? (string) $raw : '';
        if (!is_string($raw) || !$this->isDate($raw)) {
            $errors[$field] = 'Enter a valid date in YYYY-MM-DD format.';
            return null;
        }

        $values[$field] = $raw;
        return $raw;
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }
}
