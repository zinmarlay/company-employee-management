<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\DTO\SystemUserInput;

final readonly class SystemUserValidationResult
{
    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    public function __construct(
        public array $values,
        public ?SystemUserInput $input,
        public array $errors,
    ) {
    }

    public function isValid(): bool
    {
        return $this->input instanceof SystemUserInput && $this->errors === [];
    }
}
