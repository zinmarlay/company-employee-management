<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\DTO\DepartmentInput;
use App\Application\Support\Clock;
use App\Application\Validation\DepartmentInputValidator;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentDuplicateException;
use App\Domain\Organization\DepartmentRepositoryInterface;
use App\Domain\Organization\DepartmentCatalog;
use App\Localization\Translator;

final class DepartmentService
{
    private const LIST_LIMIT = 200;

    private readonly DepartmentCatalog $catalog;
    private readonly OrganizationDisplayNameResolver $displayNames;

    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
        private readonly BranchRepositoryInterface $branches,
        private readonly DepartmentInputValidator $validator,
        private readonly Clock $clock,
        ?DepartmentCatalog $catalog = null,
        ?OrganizationDisplayNameResolver $displayNames = null,
        private readonly ?Translator $translator = null,
    ) {
        $this->catalog = $catalog ?? new DepartmentCatalog();
        $this->displayNames = $displayNames ?? new OrganizationDisplayNameResolver(new \App\Domain\Organization\PrefectureCatalog(), $this->catalog);
    }

    /** @return array<int, array<string, mixed>> */
    public function listDepartments(): array
    {
        return array_map(fn (array $department): array => $this->decorateDepartment($department), $this->departments->listManagement(self::LIST_LIMIT));
    }

    /** @return array<string, mixed>|null */
    public function getDepartment(int $id): ?array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return null;
        }

        $department = $this->decorateDepartment($department);
        $department['employees'] = $this->departments->listEmployeesByDepartment($id);
        return $department;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData([
            'branch_id' => '',
            'department_code' => '',
            'description' => '',
        ], [], null);
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return null;
        }
        if (!$this->canManage($department)) {
            return null;
        }

        $department = $this->decorateDepartment($department);
        return $this->formData([
            'branch_id' => (int) $department['branch_id'],
            'department_code' => (string) $department['code'],
            'code' => (string) $department['code'],
            'name' => (string) $department['name'],
            'description' => $department['description'] ?? '',
        ], [], $department);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function createDepartment(array $rawInput): array
    {
        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->failure(null, $validation->values, $validation->errors);
        }

        $input = $validation->input;
        $department = $this->catalog->find($input->departmentCode);
        if ($department === null) {
            return $this->failure(null, $validation->values, ['department_code' => 'Select a supported department type.']);
        }

        $errors = $this->businessErrors($input->branchId, $department['code'], null);
        if ($errors !== []) {
            return $this->failure(null, $validation->values, $errors);
        }

        $canonical = new DepartmentInput(
            $input->branchId,
            $department['code'],
            $department['name'],
            $input->description,
        );

        try {
            $now = $this->now();
            $id = $this->departments->insert($canonical, $now, $now);
        } catch (DepartmentDuplicateException) {
            return $this->failure(null, $validation->values, ['department_code' => 'A department of this type already exists for the selected branch.']);
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function updateDepartment(int $id, array $rawInput): array
    {
        $current = $this->departments->findById($id);
        if ($current === null) {
            return $this->failure(null, [], []);
        }

        $validation = $this->validator->validateMetadata($rawInput);
        $values = $this->editValues($current, $validation->values);
        if (!$validation->isValid()) {
            return $this->failure($id, $values, $validation->errors);
        }

        if (!$this->canManage($current)) {
            return $this->failure(null, [], []);
        }
        if (!$this->departments->updateMetadata($id, $validation->input, $this->now())) {
            return $this->failure(null, [], []);
        }

        return ['success' => true, 'id' => $id, 'values' => $values, 'errors' => []];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        $department = $this->departments->findById($id);
        return $department !== null && $this->canManage($department) ? $this->decorateDepartment($department) : null;
    }

    /** @return array{status: string, department: array<string, mixed>|null} */
    public function deactivateDepartment(int $id): array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return ['status' => 'missing', 'department' => null];
        }
        if ((string) $department['status'] !== 'active') {
            return ['status' => 'already-inactive', 'department' => $this->decorateDepartment($department)];
        }
        if (!$this->parentBranchIsActive($department)) {
            return ['status' => 'parent-inactive', 'department' => $this->decorateDepartment($department)];
        }

        $this->departments->deactivate($id, $this->now());
        $updated = $this->departments->findById($id);
        return ['status' => 'deactivated', 'department' => $updated === null ? null : $this->decorateDepartment($updated)];
    }

    /** @return array<string, mixed> */
    private function formData(array $values, array $errors, ?array $department): array
    {
        $branches = $this->branches->listActive();
        if ($department !== null && !$this->containsId($branches, (int) $department['branch_id'])) {
            $currentBranch = $this->branches->findById((int) $department['branch_id']);
            if ($currentBranch !== null) {
                $branches[] = $currentBranch;
            }
        }

        $locale = $this->translator?->locale() ?? 'en';
        $branches = array_map(
            fn (array $branch): array => $this->displayNames->branch($branch, $locale),
            $branches,
        );
        $departmentTypes = array_map(
            fn (array $type): array => $this->displayNames->departmentTypeOption($type, $locale),
            $this->catalog->all(),
        );

        usort($branches, static fn (array $left, array $right): int => [
            (string) ($left['name'] ?? ''),
            (int) ($left['id'] ?? 0),
        ] <=> [
            (string) ($right['name'] ?? ''),
            (int) ($right['id'] ?? 0),
        ]);

        return [
            'values' => $values,
            'errors' => $errors,
            'branches' => $branches,
            'departmentTypes' => $departmentTypes,
            'department' => $department,
        ];
    }

    /** @param array<string, mixed> $current @param array<string, mixed> $metadata */
    private function editValues(array $current, array $metadata): array
    {
        return [
            'branch_id' => (int) $current['branch_id'],
            'department_code' => (string) $current['code'],
            'code' => (string) $current['code'],
            'name' => (string) $current['name'],
            'description' => $metadata['description'] ?? ($current['description'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function decorateDepartment(array $department): array
    {
        $catalog = $this->catalog->find((string) ($department['code'] ?? ''));
        $department['department_label_ja'] = $catalog['name'] ?? null;
        $department['department_label_en'] = $catalog['label_en'] ?? null;

        return $this->displayNames->department($department, $this->translator?->locale() ?? 'en');
    }

    /** @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(?int $id, array $values, array $errors): array
    {
        return ['success' => false, 'id' => $id, 'values' => $values, 'errors' => $errors];
    }

    /** @return array<string, string> */
    private function businessErrors(int $branchId, string $code, ?int $exceptId): array
    {
        $errors = [];
        $branch = $this->branches->findById($branchId);
        if ($branch === null) {
            $errors['branch_id'] = 'Select an existing branch.';
        } elseif ((string) $branch['status'] !== 'active') {
            $errors['branch_id'] = 'Select an active branch.';
        }

        if ($this->departments->codeExists($branchId, $code, $exceptId)) {
            $errors['department_code'] = 'A department of this type already exists for the selected branch.';
        }

        return $errors;
    }

    private function containsId(array $rows, int $id): bool
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $department */
    private function canManage(array $department): bool
    {
        return (string) ($department['status'] ?? '') === 'active'
            && $this->parentBranchIsActive($department);
    }

    /** @param array<string, mixed> $department */
    private function parentBranchIsActive(array $department): bool
    {
        $branch = $this->branches->findById((int) ($department['branch_id'] ?? 0));
        return $branch !== null && (string) ($branch['status'] ?? '') === 'active';
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
