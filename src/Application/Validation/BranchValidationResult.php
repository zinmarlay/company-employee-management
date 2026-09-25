<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\BranchInput;

final readonly class BranchValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?BranchInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof BranchInput && $this->errors === [];
    }
}
