<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeCertificationRepositoryInterface;
use App\Domain\Employee\EmployeeProjectRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Employee\SkillRepositoryInterface;

final class EmployeePortfolioSummaryService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly SkillRepositoryInterface $skills,
        private readonly EmployeeProjectRepositoryInterface $projects,
        private readonly EmployeeCertificationRepositoryInterface $certifications,
    ) {
    }

    /** @return array<string, int>|null */
    public function summary(int $employeeId): ?array
    {
        if ($this->employees->findById($employeeId) === null) {
            return null;
        }

        return [
            'skills' => $this->skills->countActiveForEmployee($employeeId),
            'projects' => $this->projects->countActiveForEmployee($employeeId),
            'certifications' => $this->certifications->countActiveForEmployee($employeeId),
        ];
    }
}

