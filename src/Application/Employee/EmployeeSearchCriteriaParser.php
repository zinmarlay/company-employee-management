<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\DTO\EmployeeSearchCriteria;

final class EmployeeSearchCriteriaParser
{
    private const DEFAULT_SORT = 'employee_code';
    private const DEFAULT_DIRECTION = 'asc';
    private const MAX_PAGE = 1_000_000;

    /** @var array<int, string> */
    private const SORTS = ['employee_code', 'name', 'branch', 'department', 'employee_type', 'status'];

    /** @var array<int, string> */
    private const TYPES = ['permanent', 'dispatched'];

    /** @var array<int, string> */
    private const STATUSES = ['active', 'inactive'];

    /** @param array<string, mixed> $query */
    public function parse(array $query): EmployeeSearchCriteria
    {
        return new EmployeeSearchCriteria(
            $this->keyword($query['keyword'] ?? null),
            $this->positiveInteger($query['branch_id'] ?? null),
            $this->positiveInteger($query['department_id'] ?? null),
            $this->whitelisted($query['employee_type'] ?? null, self::TYPES),
            $this->whitelisted($query['status'] ?? null, self::STATUSES),
            $this->whitelisted($query['sort'] ?? null, self::SORTS) ?? self::DEFAULT_SORT,
            $this->whitelisted($query['direction'] ?? null, ['asc', 'desc']) ?? self::DEFAULT_DIRECTION,
            $this->page($query['page'] ?? null),
        );
    }

    private function keyword(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $value = (string) $value;
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return is_int($integer) ? $integer : null;
    }

    /** @param array<int, string> $allowed */
    private function whitelisted(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private function page(mixed $value): int
    {
        $page = $this->positiveInteger($value);
        return $page === null || $page > self::MAX_PAGE ? 1 : $page;
    }
}
