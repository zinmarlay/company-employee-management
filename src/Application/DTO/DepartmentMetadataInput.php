<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DepartmentMetadataInput
{
    public function __construct(public ?string $description)
    {
    }
}
