<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DepartmentMetadataInput;

final readonly class DepartmentMetadataValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?DepartmentMetadataInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof DepartmentMetadataInput && $this->errors === [];
    }
}
