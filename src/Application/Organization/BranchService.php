<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\DTO\BranchInput;
use App\Application\Support\Clock;
use App\Application\Validation\BranchInputValidator;
use App\Domain\Organization\BranchDuplicateException;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentRepositoryInterface;

final class BranchService
{
    private const LIST_LIMIT = 200;

    public function __construct(
        private readonly BranchRepositoryInterface $branches,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly BranchInputValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listBranches(): array
    {
        return $this->branches->listManagement(self::LIST_LIMIT);
    }

    /** @return array<string, mixed>|null */
    public function getBranch(int $id): ?array
    {
        $branch = $this->branches->findById($id);
        if ($branch === null) {
            return null;
        }

        $branch['departments'] = $this->departments->listByBranchId($id);
        return $branch;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData([
            'company_id' => '',
            'code' => '',
            'name' => '',
            'city' => '',
            'address' => '',
            'phone' => '',
        ], [], null);
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $branch = $this->branches->findById($id);
        if ($branch === null) {
            return null;
        }

        return $this->formData([
            'company_id' => (int) $branch['company_id'],
            'code' => (string) $branch['code'],
            'name' => (string) $branch['name'],
            'city' => (string) $branch['city'],
            'address' => (string) $branch['address'],
            'phone' => (string) $branch['phone'],
        ], [], $branch);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function createBranch(array $rawInput): array
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
            $id = $this->branches->insert($validation->input, $now, $now);
        } catch (BranchDuplicateException) {
            return $this->failure(null, $validation->values, ['code' => 'A branch with this code already exists for the selected company.']);
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function updateBranch(int $id, array $rawInput): array
    {
        $current = $this->branches->findById($id);
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
            $this->branches->update($id, $validation->input, $this->now());
        } catch (BranchDuplicateException) {
            return $this->failure($id, $validation->values, ['code' => 'A branch with this code already exists for the selected company.']);
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        return $this->branches->findById($id);
    }

    /** @return array{status: string, branch: array<string, mixed>|null} */
    public function deactivateBranch(int $id): array
    {
        $branch = $this->branches->findById($id);
        if ($branch === null) {
            return ['status' => 'missing', 'branch' => null];
        }
        if ((string) $branch['status'] !== 'active') {
            return ['status' => 'already-inactive', 'branch' => $branch];
        }

        $this->branches->deactivate($id, $this->now());
        return ['status' => 'deactivated', 'branch' => $this->branches->findById($id)];
    }

    /** @return array<string, mixed> */
    private function formData(array $values, array $errors, ?array $branch): array
    {
        return [
            'values' => $values,
            'errors' => $errors,
            'companies' => $this->branches->listCompanies(),
            'branch' => $branch,
        ];
    }

    /** @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(?int $id, array $values, array $errors): array
    {
        return ['success' => false, 'id' => $id, 'values' => $values, 'errors' => $errors];
    }

    /** @return array<string, string> */
    private function businessErrors(BranchInput $input, ?int $exceptId, ?array $current): array
    {
        $errors = [];
        if ($this->branches->findCompanyById($input->companyId) === null) {
            $errors['company_id'] = 'Select an existing company.';
        }

        if ($current !== null && (int) $current['company_id'] !== $input->companyId) {
            $errors['company_id'] = 'The branch company cannot be changed.';
        }

        if ($this->branches->codeExists($input->companyId, $input->code, $exceptId)) {
            $errors['code'] = 'A branch with this code already exists for the selected company.';
        }

        return $errors;
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
