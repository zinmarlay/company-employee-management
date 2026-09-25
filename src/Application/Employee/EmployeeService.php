<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\DTO\EmployeeInput;
use App\Application\Support\Clock;
use App\Application\Validation\EmployeeInputValidator;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use App\Domain\Employee\EmployeeDuplicateException;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Organization\BranchReadRepositoryInterface;
use App\Domain\Organization\DepartmentReadRepositoryInterface;

final class EmployeeService
{
    private const LIST_LIMIT = 200;

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly BranchReadRepositoryInterface $branches,
        private readonly DepartmentReadRepositoryInterface $departments,
        private readonly EmployeeInputValidator $validator,
        private readonly Clock $clock,
        private readonly ?DispatchContractRepositoryInterface $dispatchContracts = null,
        private readonly ?ContractExpirationClassifier $expiration = null,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listEmployees(): array
    {
        return $this->employees->listBasic(self::LIST_LIMIT);
    }

    /** @return array<string, mixed>|null */
    public function getEmployee(int $id): ?array
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            return null;
        }

        $employee['dispatch'] = $this->dispatchData($employee);

        return $employee;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData(
            [
                'employee_code' => '',
                'first_name' => '',
                'last_name' => '',
                'first_name_kana' => '',
                'last_name_kana' => '',
                'email' => '',
                'phone' => '',
                'position_title' => '',
                'branch_id' => '',
                'department_id' => '',
                'employee_type' => 'permanent',
                'hire_date' => '',
            ],
            [],
            null,
            'create',
            null,
        );
    }

    /**
     * @param array<string, mixed> $rawInput
     * @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>}
     */
    public function createEmployee(array $rawInput): array
    {
        $validation = $this->validator->validate($rawInput);

        if (!$validation->isValid()) {
            return [
                'success' => false,
                'id' => null,
                'values' => $validation->values,
                'errors' => $validation->errors,
            ];
        }

        $errors = $this->businessErrors($validation->input, null, null);

        if ($errors !== []) {
            return [
                'success' => false,
                'id' => null,
                'values' => $validation->values,
                'errors' => $errors,
            ];
        }

        $now = $this->now();

        try {
            $id = $this->employees->insert($validation->input, $now, $now);
        } catch (EmployeeDuplicateException $exception) {
            return [
                'success' => false,
                'id' => null,
                'values' => $validation->values,
                'errors' => [$exception->field => $this->duplicateMessage($exception->field)],
            ];
        }

        return [
            'success' => true,
            'id' => $id,
            'values' => $validation->values,
            'errors' => [],
        ];
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $employee = $this->employees->findById($id);

        if ($employee === null) {
            return null;
        }

        return $this->formData(
            $this->valuesFromEmployee($employee),
            [],
            $employee,
            'edit',
            $id,
        );
    }

    /**
     * @param array<string, mixed> $rawInput
     * @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>}
     */
    public function updateEmployee(int $id, array $rawInput): array
    {
        $current = $this->employees->findById($id);

        if ($current === null) {
            return [
                'success' => false,
                'id' => null,
                'values' => [],
                'errors' => [],
            ];
        }

        $validation = $this->validator->validate($rawInput);

        if (!$validation->isValid()) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => $validation->errors,
            ];
        }

        $errors = $this->businessErrors($validation->input, $id, $current);

        if ($errors !== []) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => $errors,
            ];
        }

        try {
            $this->employees->update($id, $validation->input, $this->now());
        } catch (EmployeeDuplicateException $exception) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => [$exception->field => $this->duplicateMessage($exception->field)],
            ];
        }

        return [
            'success' => true,
            'id' => $id,
            'values' => $validation->values,
            'errors' => [],
        ];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        return $this->employees->findById($id);
    }

    /** @return array{status: string, employee: array<string, mixed>|null} */
    public function deactivateEmployee(int $id): array
    {
        $employee = $this->employees->findById($id);

        if ($employee === null) {
            return ['status' => 'missing', 'employee' => null];
        }

        if ((string) $employee['status'] !== 'active') {
            return ['status' => 'already-inactive', 'employee' => $employee];
        }

        $this->employees->deactivate($id, $this->now());

        return [
            'status' => 'deactivated',
            'employee' => $this->employees->findById($id),
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $current
     * @return array<string, mixed>
     */
    private function formData(
        array $values,
        array $errors,
        ?array $current,
        string $mode,
        ?int $id,
    ): array {
        $branchRows = $this->branches->listActive();
        $departmentRows = $this->departments->listActive();

        if ($current !== null) {
            $currentBranch = $this->branches->findById((int) $current['branch_id']);

            if ($currentBranch !== null && !$this->containsId($branchRows, (int) $currentBranch['id'])) {
                $branchRows[] = $currentBranch;
            }

            if ($current['department_id'] !== null) {
                $currentDepartment = $this->departments->findById((int) $current['department_id']);

                if ($currentDepartment !== null
                    && !$this->containsId($departmentRows, (int) $currentDepartment['id'])
                ) {
                    $departmentRows[] = $currentDepartment;
                }
            }
        }

        usort(
            $branchRows,
            static fn (array $left, array $right): int => [
                (string) $left['name'],
                (int) $left['id'],
            ] <=> [
                (string) $right['name'],
                (int) $right['id'],
            ],
        );

        usort(
            $departmentRows,
            static fn (array $left, array $right): int => [
                (int) $left['branch_id'],
                (string) $left['name'],
                (int) $left['id'],
            ] <=> [
                (int) $right['branch_id'],
                (string) $right['name'],
                (int) $right['id'],
            ],
        );

        $departmentGroups = [];

        foreach ($branchRows as $branch) {
            $departmentGroups[(int) $branch['id']] = [
                'branch' => $branch,
                'departments' => [],
            ];
        }

        foreach ($departmentRows as $department) {
            $branchId = (int) $department['branch_id'];

            if (isset($departmentGroups[$branchId])) {
                $departmentGroups[$branchId]['departments'][] = $department;
            }
        }

        return [
            'values' => $values,
            'errors' => $errors,
            'branches' => $branchRows,
            'departmentGroups' => array_values($departmentGroups),
            'mode' => $mode,
            'employeeId' => $id,
        ];
    }

    /**
     * @param array<string, mixed> $employee
     * @return array<string, mixed>
     */
    private function valuesFromEmployee(array $employee): array
    {
        return [
            'employee_code' => (string) $employee['employee_code'],
            'first_name' => (string) $employee['first_name'],
            'last_name' => (string) $employee['last_name'],
            'first_name_kana' => (string) $employee['first_name_kana'],
            'last_name_kana' => (string) $employee['last_name_kana'],
            'email' => (string) $employee['email'],
            'phone' => $employee['phone'] === null ? '' : (string) $employee['phone'],
            'position_title' => $employee['position_title'] === null
                ? ''
                : (string) $employee['position_title'],
            'branch_id' => (int) $employee['branch_id'],
            'department_id' => $employee['department_id'] === null
                ? ''
                : (int) $employee['department_id'],
            'employee_type' => (string) $employee['employee_type'],
            'hire_date' => (string) $employee['hire_date'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function containsId(array $rows, int $id): bool
    {
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function businessErrors(EmployeeInput $input, ?int $exceptId, ?array $current): array
    {
        $errors = [];
        $branch = $this->branches->findById($input->branchId);

        if ($branch === null) {
            $errors['branch_id'] = 'Select an existing branch.';
        } elseif ((string) $branch['status'] !== 'active'
            && ($current === null || (int) $current['branch_id'] !== $input->branchId)
        ) {
            $errors['branch_id'] = 'Select an active branch.';
        }

        if ($input->departmentId !== null) {
            $department = $this->departments->findById($input->departmentId);

            if ($department === null) {
                $errors['department_id'] = 'Select an existing department.';
            } elseif ((int) $department['branch_id'] !== $input->branchId) {
                $errors['department_id'] = 'Select a department from the selected branch.';
            } elseif ((string) $department['status'] !== 'active'
                && ($current === null || (int) ($current['department_id'] ?? 0) !== $input->departmentId)
            ) {
                $errors['department_id'] = 'Select an active department.';
            }
        }

        if ($this->employees->employeeCodeExists($input->employeeCode, $exceptId)) {
            $errors['employee_code'] = 'An employee with this code already exists.';
        }

        if ($this->employees->emailExists($input->email, $exceptId)) {
            $errors['email'] = 'An employee with this email already exists.';
        }

        return $errors;
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }

    private function duplicateMessage(string $field): string
    {
        return $field === 'email'
            ? 'An employee with this email already exists.'
            : 'An employee with this code already exists.';
    }

    /** @param array<string, mixed> $employee @return array<string, mixed> */
    private function dispatchData(array $employee): array
    {
        $empty = [
            'is_dispatched' => false,
            'dispatch_company' => null,
            'current_contract' => null,
            'contract_history' => [],
        ];

        if (($employee['employee_type'] ?? null) !== 'dispatched' || !$this->dispatchContracts instanceof DispatchContractRepositoryInterface) {
            return $empty;
        }

        $classifier = $this->expiration ?? new ContractExpirationClassifier();
        $history = array_map(function (array $contract) use ($classifier): array {
            $contract['expiration_classification'] = $classifier->classify(
                (string) $contract['end_date'],
                $this->clock->nowUtc(),
            );
            return $contract;
        }, $this->dispatchContracts->findHistoryByEmployeeId((int) $employee['id']));

        $current = $history[0] ?? null;

        return [
            'is_dispatched' => true,
            'dispatch_company' => $current === null ? null : [
                'id' => $current['dispatch_company_id'],
                'code' => $current['dispatch_company_code'],
                'name' => $current['dispatch_company_name'],
                'status' => $current['dispatch_company_status'],
            ],
            'current_contract' => $current,
            'contract_history' => $history,
        ];
    }
}
