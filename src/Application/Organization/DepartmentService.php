<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\DTO\DepartmentInput;
use App\Application\Support\Clock;
use App\Application\Validation\DepartmentInputValidator;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentDuplicateException;
use App\Domain\Organization\DepartmentRepositoryInterface;

final class DepartmentService
{
    private const LIST_LIMIT = 200;

    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
        private readonly BranchRepositoryInterface $branches,
        private readonly DepartmentInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listDepartments(): array
    {
        return $this->departments->listManagement(self::LIST_LIMIT);
    }

    /** @return array<string, mixed>|null */
    public function getDepartment(int $id): ?array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return null;
        }

        $department['employees'] = $this->departments->listEmployeesByDepartment($id);
        return $department;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData(['branch_id' => '', 'code' => '', 'name' => '', 'description' => ''], [], null);
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return null;
        }

        return $this->formData([
            'branch_id' => (int) $department['branch_id'],
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

        $errors = $this->businessErrors($validation->input, null, null);
        if ($errors !== []) {
            return $this->failure(null, $validation->values, $errors);
        }

        try {
            $now = $this->now();
            $id = $this->departments->insert($validation->input, $now, $now);
        } catch (DepartmentDuplicateException) {
            return $this->failure(null, $validation->values, ['code' => 'A department with this code already exists for the selected branch.']);
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

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->failure($id, $validation->values, $validation->errors);
        }

        $errors = $this->businessErrors($validation->input, $id, $current);
        if ($errors !== []) {
            return $this->failure($id, $validation->values, $errors);
        }

        try {
            $this->departments->update($id, $validation->input, $this->now());
        } catch (DepartmentDuplicateException) {
            return $this->failure($id, $validation->values, ['code' => 'A department with this code already exists for the selected branch.']);
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        return $this->departments->findById($id);
    }

    /** @return array{status: string, department: array<string, mixed>|null} */
    public function deactivateDepartment(int $id): array
    {
        $department = $this->departments->findById($id);
        if ($department === null) {
            return ['status' => 'missing', 'department' => null];
        }
        if ((string) $department['status'] !== 'active') {
            return ['status' => 'already-inactive', 'department' => $department];
        }

        $this->departments->deactivate($id, $this->now());
        return ['status' => 'deactivated', 'department' => $this->departments->findById($id)];
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

        usort($branches, static fn (array $left, array $right): int => [
            (string) ($left['name'] ?? ''),
            (int) ($left['id'] ?? 0),
        ] <=> [
            (string) ($right['name'] ?? ''),
            (int) ($right['id'] ?? 0),
        ]);

        return ['values' => $values, 'errors' => $errors, 'branches' => $branches, 'department' => $department];
    }

    /** @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(?int $id, array $values, array $errors): array
    {
        return ['success' => false, 'id' => $id, 'values' => $values, 'errors' => $errors];
    }

    /** @return array<string, string> */
    private function businessErrors(DepartmentInput $input, ?int $exceptId, ?array $current): array
    {
        $errors = [];
        $branch = $this->branches->findById($input->branchId);
        if ($branch === null) {
            $errors['branch_id'] = 'Select an existing branch.';
        } elseif ($current === null && (string) $branch['status'] !== 'active') {
            $errors['branch_id'] = 'Select an active branch.';
        }

        if ($current !== null && (int) $current['branch_id'] !== $input->branchId) {
            $errors['branch_id'] = 'The department branch cannot be changed.';
        }

        if ($this->departments->codeExists($input->branchId, $input->code, $exceptId)) {
            $errors['code'] = 'A department with this code already exists for the selected branch.';
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

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
