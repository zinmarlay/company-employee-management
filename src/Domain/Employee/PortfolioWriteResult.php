<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final readonly class PortfolioWriteResult
{
    public function __construct(
        public string $status,
        public ?int $id = null,
    ) {
    }

    public function succeeded(): bool
    {
        return in_array($this->status, ['created', 'updated', 'restored', 'archived'], true);
    }
}

