<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\DispatchCompanyInput;

final readonly class DispatchCompanyValidationResult
{
    /** @param array<string, mixed> $values @param array<string, string> $errors */
    public function __construct(
        public array $values,
        public ?DispatchCompanyInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof DispatchCompanyInput && $this->errors === [];
    }
}
