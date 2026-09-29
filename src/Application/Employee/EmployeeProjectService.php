<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\Support\Clock;
use App\Application\Validation\EmployeeProjectInputValidator;
use App\Domain\Employee\EmployeeProjectRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Employee\PortfolioWriteResult;

final class EmployeeProjectService
{
    public function __construct(
        private readonly EmployeeProjectRepositoryInterface $projects,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeProjectInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<string,mixed> */
    public function list(int $employeeId): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) return ['status' => 'missing', 'employee' => null, 'records' => []];

        $records = $this->projects->listForEmployee($employeeId, true);
        return [
            'status' => 'ok',
            'employee' => $employee,
            'records' => $records,
            'activeRecords' => array_values(array_filter($records, static fn (array $row): bool => ($row['status'] ?? '') === 'active')),
            'archivedRecords' => array_values(array_filter($records, static fn (array $row): bool => ($row['status'] ?? '') === 'archived')),
        ];
    }

    /** @return array<string,mixed> */
    public function createForm(int $employeeId): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) return ['status' => 'missing', 'employee' => null, 'values' => [], 'errors' => []];
        if (($employee['status'] ?? '') !== 'active') return ['status' => 'inactive', 'employee' => $employee, 'values' => [], 'errors' => []];

        return ['status' => 'editable', 'employee' => $employee, 'values' => $this->emptyValues(), 'errors' => []];
    }

    /** @return array<string,mixed> */
    public function detail(int $employeeId, int $projectId): array
    {
        $employee = $this->employees->findById($employeeId);
        $project = $this->projects->findByIdForEmployee($employeeId, $projectId);
        if ($employee === null || $project === null) return ['status' => 'missing', 'employee' => $employee, 'project' => null];

        return ['status' => 'ok', 'employee' => $employee, 'project' => $project];
    }

    /** @return array<string,mixed> */
    public function editForm(int $employeeId, int $projectId): array
    {
        $detail = $this->detail($employeeId, $projectId);
        if ($detail['status'] === 'missing') return ['status' => 'missing', ...$detail, 'values' => [], 'errors' => []];
        if (($detail['employee']['status'] ?? '') !== 'active') return ['status' => 'inactive', ...$detail, 'values' => $this->valuesFromRow($detail['project']), 'errors' => []];
        if (($detail['project']['status'] ?? '') !== 'active') return ['status' => 'archived', ...$detail, 'values' => $this->valuesFromRow($detail['project']), 'errors' => []];

        return ['status' => 'editable', ...$detail, 'values' => $this->valuesFromRow($detail['project']), 'errors' => []];
    }

    /** @return array<string,mixed> */
    public function create(int $employeeId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) return $this->result('missing', [], [], null);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], null);

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) return $this->result('invalid', $validation->values, $validation->errors, null);

        $now = $this->now();
        $write = $this->projects->insert($employeeId, $validation->input, $now, $now);
        return $this->result($write->status, $validation->values, [], $write->id);
    }

    /** @return array<string,mixed> */
    public function update(int $employeeId, int $projectId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        $project = $this->projects->findByIdForEmployee($employeeId, $projectId);
        if ($employee === null || $project === null) return $this->result('missing', [], [], $projectId);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], $projectId);

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) return $this->result('invalid', $validation->values, $validation->errors, $projectId);

        $write = $this->projects->update($employeeId, $projectId, $validation->input, $this->now());
        return $this->result($write->status, $validation->values, [], $write->id ?? $projectId);
    }

    /** @return array<string,mixed> */
    public function archive(int $employeeId, int $projectId): array
    {
        $employee = $this->employees->findById($employeeId);
        $project = $this->projects->findByIdForEmployee($employeeId, $projectId);
        if ($employee === null || $project === null) return $this->result('missing', [], [], $projectId);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], $projectId);

        $write = $this->projects->archive($employeeId, $projectId, $this->now());
        return $this->result($write->status, [], [], $projectId);
    }

    /** @return array<string,mixed> */
    private function result(string $status, array $values, array $errors, ?int $id): array
    {
        return ['status' => $status, 'values' => $values, 'errors' => $errors, 'id' => $id];
    }

    /** @return array<string,mixed> */
    private function emptyValues(): array
    {
        return [
            'project_name' => '', 'role' => '', 'start_date' => '', 'end_date' => '',
            'description' => '', 'responsibilities' => '', 'technologies' => '',
        ];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function valuesFromRow(array $row): array
    {
        return [
            'project_name' => (string) ($row['project_name'] ?? ''),
            'role' => (string) ($row['role'] ?? ''),
            'start_date' => (string) ($row['start_date'] ?? ''),
            'end_date' => $row['end_date'] === null ? '' : (string) $row['end_date'],
            'description' => $row['description'] === null ? '' : (string) $row['description'],
            'responsibilities' => $row['responsibilities'] === null ? '' : (string) $row['responsibilities'],
            'technologies' => $row['technologies'] === null ? '' : (string) $row['technologies'],
        ];
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
