<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\Support\Clock;
use App\Application\Validation\EmployeeCertificationInputValidator;
use App\Domain\Employee\EmployeeCertificationRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Employee\PortfolioDuplicateException;

final class EmployeeCertificationService
{
    public function __construct(
        private readonly EmployeeCertificationRepositoryInterface $certifications,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeCertificationInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<string,mixed> */
    public function list(int $employeeId): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) return ['status' => 'missing', 'employee' => null, 'records' => []];

        $records = $this->certifications->listForEmployee($employeeId, true);
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
    public function detail(int $employeeId, int $certificationId): array
    {
        $employee = $this->employees->findById($employeeId);
        $certification = $this->certifications->findByIdForEmployee($employeeId, $certificationId);
        if ($employee === null || $certification === null) return ['status' => 'missing', 'employee' => $employee, 'certification' => null];

        return ['status' => 'ok', 'employee' => $employee, 'certification' => $certification];
    }

    /** @return array<string,mixed> */
    public function editForm(int $employeeId, int $certificationId): array
    {
        $detail = $this->detail($employeeId, $certificationId);
        if ($detail['status'] === 'missing') return ['status' => 'missing', ...$detail, 'values' => [], 'errors' => []];
        if (($detail['employee']['status'] ?? '') !== 'active') return ['status' => 'inactive', ...$detail, 'values' => $this->valuesFromRow($detail['certification']), 'errors' => []];
        if (($detail['certification']['status'] ?? '') !== 'active') return ['status' => 'archived', ...$detail, 'values' => $this->valuesFromRow($detail['certification']), 'errors' => []];

        return ['status' => 'editable', ...$detail, 'values' => $this->valuesFromRow($detail['certification']), 'errors' => []];
    }

    /** @return array<string,mixed> */
    public function create(int $employeeId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        if ($employee === null) return $this->result('missing', [], [], null);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], null);

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) return $this->result('invalid', $validation->values, $validation->errors, null);

        try {
            $now = $this->now();
            $write = $this->certifications->insert($employeeId, $validation->input, $now, $now);
        } catch (PortfolioDuplicateException) {
            return $this->result('invalid', $validation->values, ['certification_name' => 'This certification already exists for this Employee and obtained date.'], null);
        }

        if ($write->status === 'duplicate') {
            return $this->result('invalid', $validation->values, ['certification_name' => 'This certification already exists for this Employee and obtained date.'], $write->id);
        }

        return $this->result($write->status, $validation->values, [], $write->id);
    }

    /** @return array<string,mixed> */
    public function update(int $employeeId, int $certificationId, array $rawInput): array
    {
        $employee = $this->employees->findById($employeeId);
        $certification = $this->certifications->findByIdForEmployee($employeeId, $certificationId);
        if ($employee === null || $certification === null) return $this->result('missing', [], [], $certificationId);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], $certificationId);

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) return $this->result('invalid', $validation->values, $validation->errors, $certificationId);

        $write = $this->certifications->update($employeeId, $certificationId, $validation->input, $this->now());
        if ($write->status === 'duplicate') {
            return $this->result('invalid', $validation->values, ['certification_name' => 'This certification already exists for this Employee and obtained date.'], $certificationId);
        }

        return $this->result($write->status, $validation->values, [], $write->id ?? $certificationId);
    }

    /** @return array<string,mixed> */
    public function archive(int $employeeId, int $certificationId): array
    {
        $employee = $this->employees->findById($employeeId);
        $certification = $this->certifications->findByIdForEmployee($employeeId, $certificationId);
        if ($employee === null || $certification === null) return $this->result('missing', [], [], $certificationId);
        if (($employee['status'] ?? '') !== 'active') return $this->result('inactive', [], [], $certificationId);

        $write = $this->certifications->archive($employeeId, $certificationId, $this->now());
        return $this->result($write->status, [], [], $certificationId);
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
            'certification_name' => '', 'issuing_organization' => '',
            'obtained_date' => '', 'expiration_date' => '',
            'credential_identifier' => '', 'notes' => '',
        ];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function valuesFromRow(array $row): array
    {
        return [
            'certification_name' => (string) ($row['certification_name'] ?? ''),
            'issuing_organization' => (string) ($row['issuing_organization'] ?? ''),
            'obtained_date' => (string) ($row['obtained_date'] ?? ''),
            'expiration_date' => $row['expiration_date'] === null ? '' : (string) $row['expiration_date'],
            'credential_identifier' => $row['credential_identifier'] === null ? '' : (string) $row['credential_identifier'],
            'notes' => $row['notes'] === null ? '' : (string) $row['notes'],
        ];
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
