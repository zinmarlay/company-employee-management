<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\EmployeeInput;

final readonly class EmployeeValidationResult
{
    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    public function __construct(
        public array $values,
        public ?EmployeeInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof EmployeeInput && $this->errors === [];
    }
}
