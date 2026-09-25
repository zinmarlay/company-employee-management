<?php

declare(strict_types=1);

namespace App\Application\Dispatch;

use App\Application\Support\Clock;
use App\Application\Validation\DispatchContractInputValidator;
use App\Domain\Dispatch\DispatchCompanyRepositoryInterface;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use App\Domain\Employee\EmployeeRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;

final class DispatchContractService
{
    private const EMPLOYEE_LIST_LIMIT = 200;
    private const COMPANY_LIST_LIMIT = 200;

    public function __construct(
        private readonly DispatchContractRepositoryInterface $contracts,
        private readonly DispatchCompanyRepositoryInterface $companies,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DispatchContractInputValidator $validator,
        private readonly Clock $clock,
        private readonly ContractExpirationClassifier $expiration,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function getContract(int $id): ?array
    {
        $contract = $this->contracts->findById($id);
        return $contract === null ? null : $this->withExpiration($contract);
    }

    /** @return array<string, mixed> */
    public function createForm(?int $employeeId = null): array
    {
        return $this->formData([
            'employee_id' => $employeeId ?? '',
            'dispatch_company_id' => '',
            'start_date' => '',
            'end_date' => '',
        ], [], null, null);
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $contract = $this->contracts->findById($id);
        if ($contract === null) {
            return null;
        }

        return $this->formData($this->valuesFromContract($contract), [], $contract, null);
    }

    /** @return array<string, mixed>|null */
    public function renewForm(int $id): ?array
    {
        $contract = $this->contracts->findById($id);
        if ($contract === null) {
            return null;
        }

        $endDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            (string) $contract['end_date'],
            new DateTimeZone('UTC'),
        );
        $startDate = ($endDate === false ? $this->clock->nowUtc() : $endDate)->modify('+1 day')->format('Y-m-d');
        return $this->formData([
            'employee_id' => (int) $contract['employee_id'],
            'dispatch_company_id' => (int) $contract['dispatch_company_id'],
            'start_date' => $startDate,
            'end_date' => '',
        ], [], $contract, $contract);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function createContract(array $rawInput): array
    {
        return $this->persist($rawInput, null, false);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function updateContract(int $id, array $rawInput): array
    {
        $current = $this->contracts->findById($id);
        if ($current === null) {
            return $this->failure([], []);
        }

        return $this->persist($rawInput, $current, true);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function renewContract(int $id, array $rawInput): array
    {
        $source = $this->contracts->findById($id);
        if ($source === null) {
            return $this->failure([], []);
        }

        return $this->persist($rawInput, $source, false);
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForEmployee(int $employeeId): array
    {
        return array_map(
            fn (array $contract): array => $this->withExpiration($contract),
            $this->contracts->findHistoryByEmployeeId($employeeId),
        );
    }

    /** @param array<string, mixed> $rawInput @param array<string, mixed>|null $current @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function persist(array $rawInput, ?array $current, bool $editing): array
    {
        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->failure($validation->values, $validation->errors, $current['id'] ?? null);
        }

        $errors = $this->businessErrors($validation->input, $current, $editing);
        if ($errors !== []) {
            return $this->failure($validation->values, $errors, $current['id'] ?? null);
        }

        $now = $this->clock->nowUtc()->format('Y-m-d H:i:s');
        if ($editing) {
            $this->contracts->update((int) $current['id'], $validation->input, $now);
            return ['success' => true, 'id' => (int) $current['id'], 'values' => $validation->values, 'errors' => []];
        }

        $id = $this->contracts->insert($validation->input, $now, $now);
        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @return array<string, string> */
    private function businessErrors(\App\Application\DTO\DispatchContractInput $input, ?array $current, bool $editing): array
    {
        $errors = [];
        $employee = $this->employees->findById($input->employeeId);
        if ($employee === null) {
            $errors['employee_id'] = 'Select an existing employee.';
        } elseif ((string) $employee['employee_type'] !== 'dispatched') {
            $errors['employee_id'] = 'Only dispatched employees can receive a dispatch contract.';
        }

        $company = $this->companies->findById($input->dispatchCompanyId);
        if ($company === null) {
            $errors['dispatch_company_id'] = 'Select an existing dispatch company.';
        } elseif ((string) $company['status'] !== 'active'
            && (!$editing || $current === null || (int) $current['dispatch_company_id'] !== $input->dispatchCompanyId)
        ) {
            $errors['dispatch_company_id'] = 'Select an active dispatch company.';
        }

        if ($this->contracts->hasOverlap(
            $input->employeeId,
            $input->startDate,
            $input->endDate,
            $editing && $current !== null ? (int) $current['id'] : null,
        )) {
            $errors['start_date'] = 'This contract overlaps an existing period.';
            $errors['end_date'] = 'This contract overlaps an existing period.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors @return array<string, mixed> */
    private function formData(array $values, array $errors, ?array $current, ?array $source): array
    {
        $employees = array_values(array_filter(
            $this->employees->listBasic(self::EMPLOYEE_LIST_LIMIT),
            static fn (array $employee): bool => ($employee['employee_type'] ?? null) === 'dispatched',
        ));
        $companies = array_values(array_filter(
            $this->companies->listBasic(self::COMPANY_LIST_LIMIT),
            static fn (array $company): bool => ($company['status'] ?? null) === 'active',
        ));

        if ($current !== null && ($current['dispatch_company_status'] ?? 'active') !== 'active') {
            $currentCompany = $this->companies->findById((int) $current['dispatch_company_id']);
            if ($currentCompany !== null) {
                $companies[] = $currentCompany;
            }
        }

        usort($employees, static fn (array $left, array $right): int => [(string) $left['last_name'], (string) $left['first_name'], (int) $left['id']]
            <=> [(string) $right['last_name'], (string) $right['first_name'], (int) $right['id']]);
        usort($companies, static fn (array $left, array $right): int => [(string) $left['name'], (string) $left['code'], (int) $left['id']]
            <=> [(string) $right['name'], (string) $right['code'], (int) $right['id']]);

        return [
            'values' => $values,
            'errors' => $errors,
            'employees' => $employees,
            'dispatchCompanies' => $companies,
            'contract' => $current,
            'sourceContract' => $source,
        ];
    }

    /** @return array<string, mixed> */
    private function valuesFromContract(array $contract): array
    {
        return [
            'employee_id' => (int) $contract['employee_id'],
            'dispatch_company_id' => (int) $contract['dispatch_company_id'],
            'start_date' => (string) $contract['start_date'],
            'end_date' => (string) $contract['end_date'],
        ];
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(array $values, array $errors, ?int $id = null): array
    {
        return ['success' => false, 'id' => $id, 'values' => $values, 'errors' => $errors];
    }

    /** @param array<string, mixed> $contract @return array<string, mixed> */
    private function withExpiration(array $contract): array
    {
        $contract['expiration_classification'] = $this->expiration->classify(
            (string) $contract['end_date'],
            $this->clock->nowUtc(),
        );
        return $contract;
    }
}
