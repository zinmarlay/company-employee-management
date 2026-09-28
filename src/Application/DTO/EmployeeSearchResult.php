<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeSearchResult
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(
        public array $rows,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
