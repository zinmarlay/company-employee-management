<?php

declare(strict_types=1);

namespace App\Application\Employee;

use App\Application\DTO\EmployeeInput;
use App\Application\Support\Clock;
use App\Application\Validation\EmployeeInputValidator;
use App\Application\Dispatch\ContractExpirationClassifier;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;
use App\Domain\Employee\EmployeeDuplicateException;
use App\Domain\Employee\EmployeeCodeSequenceExhaustedException;
use App\Domain\Employee\EmployeeRepositoryInterface;
use App\Domain\Organization\BranchReadRepositoryInterface;
use App\Domain\Organization\DepartmentCatalog;
use App\Domain\Organization\DepartmentReadRepositoryInterface;
use App\Domain\Organization\PrefectureCatalog;
use App\Localization\Translator;
use App\Application\Organization\OrganizationDisplayNameResolver;

final class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly BranchReadRepositoryInterface $branches,
        private readonly DepartmentReadRepositoryInterface $departments,
        private readonly EmployeeInputValidator $validator,
        private readonly Clock $clock,
        private readonly ?DispatchContractRepositoryInterface $dispatchContracts = null,
        private readonly ?ContractExpirationClassifier $expiration = null,
        ?EmployeeSearchCriteriaParser $searchParser = null,
        ?PrefectureCatalog $prefectures = null,
        ?DepartmentCatalog $departmentCatalog = null,
        private readonly ?Translator $translator = null,
        ?OrganizationDisplayNameResolver $displayNames = null,
    ) {
        $this->searchParser = $searchParser ?? new EmployeeSearchCriteriaParser();
        $this->prefectures = $prefectures ?? new PrefectureCatalog();
        $this->departmentCatalog = $departmentCatalog ?? new DepartmentCatalog();
        $this->displayNames = $displayNames ?? new OrganizationDisplayNameResolver($this->prefectures, $this->departmentCatalog);
    }

    private readonly EmployeeSearchCriteriaParser $searchParser;
    private readonly PrefectureCatalog $prefectures;
    private readonly DepartmentCatalog $departmentCatalog;
    private readonly OrganizationDisplayNameResolver $displayNames;

    /** @param array<string, mixed> $query @return array<string, mixed> */
    public function listEmployees(array $query = []): array
    {
        $criteria = $this->searchParser->parse($query);
        $result = $this->employees->search($criteria);
        $locale = $this->translator?->locale() ?? 'en';
        $employees = array_map(
            fn (array $employee): array => $this->displayNames->employee($employee, $locale),
            $result->rows,
        );
        $branches = array_map(
            fn (array $branch): array => $this->displayNames->branch($branch, $locale),
            $this->branches->listForSearch(),
        );
        $departmentCatalog = array_map(
            fn (array $department): array => $this->displayNames->department($department, $locale),
            $this->departments->listForSearch(),
        );
        $departments = $departmentCatalog;

        if ($criteria->branchId !== null) {
            $departments = array_values(array_filter(
                $departments,
                static fn (array $department): bool => (int) ($department['branch_id'] ?? 0) === $criteria->branchId,
            ));
        }

        usort($branches, $this->organizationChoiceSorter(...));
        usort($departments, $this->departmentChoiceSorter(...));

        $queryState = $criteria->queryParameters();
        if ($result->page === 1) {
            unset($queryState['page']);
        } else {
            $queryState['page'] = $result->page;
        }

        return [
            'employees' => $employees,
            'total' => $result->total,
            'currentPage' => $result->page,
            'totalPages' => $result->totalPages(),
            'perPage' => $result->perPage,
            'criteria' => $criteria,
            'queryState' => $queryState,
            'searchBranches' => $branches,
            'searchDepartments' => $departments,
            'searchDepartmentCatalog' => $departmentCatalog,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getEmployee(int $id): ?array
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            return null;
        }

        $employee = $this->displayNames->employee($employee, $this->translator?->locale() ?? 'en');
        $employee['dispatch'] = $this->dispatchData($employee);

        return $employee;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData(
            [
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
        } catch (EmployeeCodeSequenceExhaustedException) {
            return [
                'success' => false,
                'id' => null,
                'values' => $validation->values,
                'errors' => ['form' => 'A new employee code is currently unavailable. Please contact an administrator.'],
            ];
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

    /** @return array{status: string, form: array<string, mixed>|null} */
    public function editForm(int $id): array
    {
        $employee = $this->employees->findById($id);

        if ($employee === null) {
            return ['status' => 'missing', 'form' => null];
        }

        if ((string) ($employee['status'] ?? '') !== 'active') {
            return ['status' => 'inactive', 'form' => null];
        }

        return [
            'status' => 'editable',
            'form' => $this->formData(
                $this->valuesFromEmployee($employee),
                [],
                $employee,
                'edit',
                $id,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $rawInput
     * @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>, status: string}
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
                'status' => 'missing',
            ];
        }

        if ((string) ($current['status'] ?? '') !== 'active') {
            return [
                'success' => false,
                'id' => $id,
                'values' => [],
                'errors' => [],
                'status' => 'inactive',
            ];
        }

        $validation = $this->validator->validate($rawInput);

        if (!$validation->isValid()) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => $validation->errors,
                'status' => 'invalid',
            ];
        }

        $errors = $this->businessErrors($validation->input, $id, $current);

        if ($errors !== []) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => $errors,
                'status' => 'invalid',
            ];
        }

        try {
            $updated = $this->employees->update($id, $validation->input, $this->now());
        } catch (EmployeeDuplicateException $exception) {
            return [
                'success' => false,
                'id' => $id,
                'values' => $validation->values,
                'errors' => [$exception->field => $this->duplicateMessage($exception->field)],
                'status' => 'invalid',
            ];
        }

        if (!$updated) {
            return [
                'success' => false,
                'id' => $id,
                'values' => [],
                'errors' => [],
                'status' => 'inactive',
            ];
        }

        return [
            'success' => true,
            'id' => $id,
            'values' => $validation->values,
            'errors' => [],
            'status' => 'updated',
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

        if (!$this->employees->deactivate($id, $this->now())) {
            $current = $this->employees->findById($id);

            return $current === null
                ? ['status' => 'missing', 'employee' => null]
                : ['status' => 'already-inactive', 'employee' => $current];
        }

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

        $locale = $this->translator?->locale() ?? 'en';
        $branchRows = array_map(
            fn (array $branch): array => $this->displayNames->branch($branch, $locale),
            $branchRows,
        );
        $branchById = [];
        foreach ($branchRows as $branch) {
            $branchById[(int) $branch['id']] = $branch;
        }
        $departmentRows = array_map(
            function (array $department) use ($branchById, $locale): array {
                $branch = $branchById[(int) ($department['branch_id'] ?? 0)] ?? [];
                $department['branch_code'] ??= $branch['code'] ?? '';
                $department['branch_name'] ??= $branch['name'] ?? '';

                return $this->displayNames->department($department, $locale);
            },
            $departmentRows,
        );

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
            'employeeCode' => $current === null ? null : (string) ($current['employee_code'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $employee
     * @return array<string, mixed>
     */
    private function valuesFromEmployee(array $employee): array
    {
        return [
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

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function organizationChoiceSorter(array $left, array $right): int
    {
        return [
            (string) ($left['name'] ?? ''),
            (int) ($left['id'] ?? 0),
        ] <=> [
            (string) ($right['name'] ?? ''),
            (int) ($right['id'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function departmentChoiceSorter(array $left, array $right): int
    {
        return [
            (string) ($left['branch_name'] ?? ''),
            (string) ($left['name'] ?? ''),
            (int) ($left['id'] ?? 0),
        ] <=> [
            (string) ($right['branch_name'] ?? ''),
            (string) ($right['name'] ?? ''),
            (int) ($right['id'] ?? 0),
        ];
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
