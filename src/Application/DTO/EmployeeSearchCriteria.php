<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeSearchCriteria
{
    public function __construct(
        public ?string $keyword,
        public ?int $branchId,
        public ?int $departmentId,
        public ?string $employeeType,
        public ?string $status,
        public string $sort,
        public string $direction,
        public int $page,
        public int $perPage = 20,
        public ?string $keywordError = null,
    ) {
    }

    /** @return array<string, scalar> */
    public function queryParameters(): array
    {
        $parameters = [];

        if ($this->keyword !== null) {
            $parameters['keyword'] = $this->keyword;
        }
        if ($this->branchId !== null) {
            $parameters['branch_id'] = $this->branchId;
        }
        if ($this->departmentId !== null) {
            $parameters['department_id'] = $this->departmentId;
        }
        if ($this->employeeType !== null) {
            $parameters['employee_type'] = $this->employeeType;
        }
        if ($this->status !== null) {
            $parameters['status'] = $this->status;
        }

        $parameters['sort'] = $this->sort;
        $parameters['direction'] = $this->direction;

        if ($this->page > 1) {
            $parameters['page'] = $this->page;
        }

        return $parameters;
    }
}
