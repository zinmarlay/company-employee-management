<?php

declare(strict_types=1);

namespace App\Localization;

use RuntimeException;

final class Translator
{
    /** @var array<string, array<string, mixed>> */
    private array $resources = [];

    private string $locale;

    public function __construct(
        private readonly string $resourceDirectory,
        string $defaultLocale = Locale::ENGLISH,
    ) {
        $this->loadResources();
        $this->locale = Locale::normalize($defaultLocale) ?? Locale::ENGLISH;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = Locale::normalize($locale) ?? Locale::ENGLISH;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /** @param array<string, scalar|null> $replace */
    public function get(string $key, array $replace = []): string
    {
        $value = $this->lookup($this->locale, $key);

        if (!is_string($value) && $this->locale !== Locale::ENGLISH) {
            $value = $this->lookup(Locale::ENGLISH, $key);
        }

        if (!is_string($value)) {
            return $key;
        }

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) ($replacement ?? ''), $value);
        }

        return $value;
    }

    public function validationMessage(string $message): string
    {
        $directMessages = [
            'This field is required.' => 'validation.required',
            'Enter a text value.' => 'validation.text',
            'Select a valid value.' => 'validation.select',
            'Enter a valid email address.' => 'validation.email',
            'Select a valid employee type.' => 'validation.employee_type',
            'Enter a valid date in YYYY-MM-DD format.' => 'validation.date',
            'Select an existing branch.' => 'validation.existing_branch',
            'Select an active branch.' => 'validation.active_branch',
            'Select an existing department.' => 'validation.existing_department',
            'Select a department from the selected branch.' => 'validation.department_branch',
            'Select an active department.' => 'validation.active_department',
            'An employee with this code already exists.' => 'validation.duplicate_code',
            'An employee with this email already exists.' => 'validation.duplicate_email',
            'A dispatch company with this code already exists.' => 'validation.duplicate_dispatch_company_code',
            'Select an existing employee.' => 'validation.existing_employee',
            'Only dispatched employees can receive a dispatch contract.' => 'validation.dispatched_employee',
            'Select an existing dispatch company.' => 'validation.existing_dispatch_company',
            'Select an active dispatch company.' => 'validation.active_dispatch_company',
            'Start date must be on or before end date.' => 'validation.date_order',
            'End date must be on or after start date.' => 'validation.date_order_end',
            'This contract overlaps an existing period.' => 'validation.contract_overlap',
        ];

        if (isset($directMessages[$message])) {
            return $this->get($directMessages[$message]);
        }

        if (preg_match('/^This field must be ([0-9]+) characters or fewer\.$/', $message, $matches) === 1) {
            return $this->get('validation.max_length', ['max' => $matches[1]]);
        }

        return $message;
    }

    /** @return array<string, mixed> */
    private function loadResource(string $locale): array
    {
        $file = $this->resourceDirectory . DIRECTORY_SEPARATOR . $locale . '.php';

        if (!is_file($file)) {
            return [];
        }

        $resource = require $file;

        if (!is_array($resource)) {
            throw new RuntimeException(sprintf('Translation resource must return an array: %s', $file));
        }

        return $resource;
    }

    private function loadResources(): void
    {
        foreach (Locale::supported() as $locale) {
            $this->resources[$locale] = $this->loadResource($locale);
        }
    }

    private function lookup(string $locale, string $key): mixed
    {
        $value = $this->resources[$locale] ?? [];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
