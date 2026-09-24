<?php

declare(strict_types=1);

namespace App\Domain\Organization;

interface BranchReadRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listActive(): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;
}
