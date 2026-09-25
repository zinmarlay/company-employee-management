<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DispatchContractInput;

final readonly class DispatchContractValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?DispatchContractInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof DispatchContractInput && $this->errors === [];
    }
}
