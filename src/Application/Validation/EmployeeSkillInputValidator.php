<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\EmployeeSkillInput;

final class EmployeeSkillInputValidator
{
    private const PROFICIENCIES = ['beginner', 'intermediate', 'advanced', 'expert'];

    /** @param array<string, mixed> $rawInput */
    public function validate(array $rawInput): EmployeeSkillValidationResult
    {
        $values = [];
        $errors = [];

        $skillName = $this->requiredString($rawInput, 'skill_name', 120, $values, $errors);
        $proficiency = $this->requiredString($rawInput, 'proficiency', 20, $values, $errors);
        $years = $this->optionalYears($rawInput, $values, $errors);
        $notes = $this->optionalString($rawInput, 'notes', 5000, $values, $errors);

        if ($proficiency !== null && !in_array($proficiency, self::PROFICIENCIES, true)) {
            $errors['proficiency'] = 'Select a valid proficiency level.';
        }

        $input = null;
        if ($errors === []) {
            $input = new EmployeeSkillInput($skillName, $proficiency, $years, $notes);
        }

        return new EmployeeSkillValidationResult($values, $input, $errors);
    }

    /** @param array<string,mixed> $input @param array<string,mixed> $values @param array<string,string> $errors */
    private function requiredString(array $input, string $field, int $max, array &$values, array &$errors): ?string
    {
        $raw = $input[$field] ?? null;
        if (!is_string($raw)) {
            $values[$field] = is_scalar($raw) ? (string) $raw : '';
            $errors[$field] = 'This field is required.';
            return null;
        }
        $value = preg_replace('/\s+/u', ' ', trim($raw)) ?? trim($raw);
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
    private function optionalString(array $input, string $field, int $max, array &$values, array &$errors): ?string
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
    private function optionalYears(array $input, array &$values, array &$errors): ?float
    {
        $raw = $input['years_experience'] ?? null;
        if ($raw === null || $raw === '') {
            $values['years_experience'] = '';
            return null;
        }
        if (!is_string($raw) && !is_int($raw) && !is_float($raw)) {
            $values['years_experience'] = '';
            $errors['years_experience'] = 'Enter a number between 0 and 99.9 with at most one decimal place.';
            return null;
        }
        $value = trim((string) $raw);
        $values['years_experience'] = $value;
        if (preg_match('/^(?:\d{1,2}(?:\.\d)?|99(?:\.0)?)$/', $value) !== 1 || (float) $value > 99.9) {
            $errors['years_experience'] = 'Enter a number between 0 and 99.9 with at most one decimal place.';
            return null;
        }
        return (float) $value;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

