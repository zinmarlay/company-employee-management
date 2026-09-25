<?php

declare(strict_types=1);

namespace App\Domain\Dispatch;

use RuntimeException;

final class DispatchCompanyDuplicateException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('A dispatch company with this code already exists.', $code, $previous);
    }
}
