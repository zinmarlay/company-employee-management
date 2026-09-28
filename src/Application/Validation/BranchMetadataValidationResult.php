<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\BranchMetadataInput;

final readonly class BranchMetadataValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?BranchMetadataInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof BranchMetadataInput && $this->errors === [];
    }
}
