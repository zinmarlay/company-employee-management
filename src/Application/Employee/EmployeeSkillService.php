<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\DTO\EmployeeSkillInput;
use App\Application\Support\Clock;
use App\Application\Validation\EmployeeSkillInputValidator;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Employee\PortfolioDuplicateException;
use App\Domain\Employee\PortfolioWriteResult;
use App\Domain\Employee\SkillRepositoryInterface;

final class EmployeeSkillService
{
    public function __construct(
        private readonly SkillRepositoryInterface $skills,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeSkillInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<string,mixed> */
    public function list(int $employeeId): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) {
            return ['status' => 'missing', 'employee' => null, 'records' => []];
        }

        $records = $this->skills->listForEmployee($employeeId, true);

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
        if ($employee === null) {
            return ['status' => 'missing', 'employee' => null, 'values' => [], 'errors' => []];
        }
        if (($employee['status'] ?? '') !== 'active') {
            return ['status' => 'inactive', 'employee' => $employee, 'values' => [], 'errors' => []];
        }

        return [
            'status' => 'editable',
            'employee' => $employee,
            'values' => $this->emptyValues(),
            'errors' => [],
        ];
    }

    /** @return array<string,mixed> */
    public function editForm(int $employeeId, int $assignmentId): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) {
            return ['status' => 'missing', 'employee' => null, 'values' => [], 'errors' => []];
        }

        $assignment = $this->skills->findAssignmentForEmployee($employeeId, $assignmentId);
        if ($assignment === null) {
            return ['status' => 'missing', 'employee' => $employee, 'values' => [], 'errors' => []];
        }
        if (($employee['status'] ?? '') !== 'active') {
            return ['status' => 'inactive', 'employee' => $employee, 'values' => [], 'errors' => [], 'assignment' => $assignment];
        }
        if (($assignment['status'] ?? '') !== 'active') {
            return ['status' => 'archived', 'employee' => $employee, 'values' => $this->valuesFromRow($assignment), 'errors' => [], 'assignment' => $assignment];
        }

        return [
            'status' => 'editable',
            'employee' => $employee,
            'assignment' => $assignment,
            'values' => $this->valuesFromRow($assignment),
            'errors' => [],
        ];
    }

    /** @return array<string,mixed> */
    public function create(int $employeeId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) {
            return $this->result('missing', [], [], null);
        }
        if (($employee['status'] ?? '') !== 'active') {
            return $this->result('inactive', [], [], null);
        }

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->result('invalid', $validation->values, $validation->errors, null);
        }

        try {
            $now = $this->now();
            $write = $this->skills->assign($employeeId, $validation->input, $now, $now);
        } catch (PortfolioDuplicateException) {
            return $this->result('invalid', $validation->values, ['skill_name' => 'This Employee already has this skill.'], null);
        }

        if ($write->status === 'duplicate') {
            return $this->result('invalid', $validation->values, ['skill_name' => 'This Employee already has this skill.'], $write->id);
        }

        return $this->result($write->status, $validation->values, [], $write->id);
    }

    /** @return array<string,mixed> */
    public function update(int $employeeId, int $assignmentId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        $assignment = $this->skills->findAssignmentForEmployee($employeeId, $assignmentId);
        if ($employee === null || $assignment === null) {
            return $this->result('missing', [], [], null);
        }
        if (($employee['status'] ?? '') !== 'active') {
            return $this->result('inactive', [], [], $assignmentId);
        }

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->result('invalid', $validation->values, $validation->errors, $assignmentId);
        }

        $write = $this->skills->updateAssignment($employeeId, $assignmentId, $validation->input, $this->now());
        if (in_array($write->status, ['duplicate'], true)) {
            return $this->result('invalid', $validation->values, ['skill_name' => 'This Employee already has this skill.'], $assignmentId);
        }

        return $this->result($write->status, $validation->values, [], $write->id ?? $assignmentId);
    }

    /** @return array<string,mixed> */
    public function archive(int $employeeId, int $assignmentId): array
    {
        $employee = $this->employees->findById($employeeId);
        $assignment = $this->skills->findAssignmentForEmployee($employeeId, $assignmentId);
        if ($employee === null || $assignment === null) {
            return $this->result('missing', [], [], $assignmentId);
        }
        if (($employee['status'] ?? '') !== 'active') {
            return $this->result('inactive', [], [], $assignmentId);
        }

        $write = $this->skills->archiveAssignment($employeeId, $assignmentId, $this->now());

        return $this->result($write->status, [], [], $assignmentId);
    }

    /** @return array<string,mixed> */
    private function result(string $status, array $values, array $errors, ?int $id): array
    {
        return ['status' => $status, 'values' => $values, 'errors' => $errors, 'id' => $id];
    }

    /** @return array<string,mixed> */
    private function emptyValues(): array
    {
        return ['skill_name' => '', 'proficiency' => 'intermediate', 'years_experience' => '', 'notes' => ''];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function valuesFromRow(array $row): array
    {
        return [
            'skill_name' => (string) ($row['skill_name'] ?? ''),
            'proficiency' => (string) ($row['proficiency'] ?? ''),
            'years_experience' => $row['years_experience'] === null ? '' : (string) $row['years_experience'],
            'notes' => $row['notes'] === null ? '' : (string) $row['notes'],
        ];
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
