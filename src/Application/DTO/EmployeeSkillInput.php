<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeSkillInput
{
    public function __construct(
        public string $skillName,
        public string $proficiency,
        public ?float $yearsExperience,
        public ?string $notes,
    ) {
    }
}

