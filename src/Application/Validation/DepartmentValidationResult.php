<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DepartmentInput;

final readonly class DepartmentValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?DepartmentInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof DepartmentInput && $this->errors === [];
    }
}
