<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\DTO\BranchInput;
use App\Application\Support\Clock;
use App\Application\Validation\BranchInputValidator;
use App\Domain\Organization\BranchDuplicateException;
use App\Domain\Organization\BranchRepositoryInterface;
use App\Domain\Organization\DepartmentRepositoryInterface;
use App\Domain\Organization\PrefectureCatalog;

final class BranchService
{
    private const LIST_LIMIT = 200;

    private readonly PrefectureCatalog $prefectures;

    public function __construct(
        private readonly BranchRepositoryInterface $branches,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly BranchInputValidator $validator,
        private readonly Clock $clock,
        ?PrefectureCatalog $prefectures = null,
    ) {
        $this->prefectures = $prefectures ?? new PrefectureCatalog();
    }

    /** @return array<int, array<string, mixed>> */
    public function listBranches(): array
    {
        return array_map(fn (array $branch): array => $this->decorateBranch($branch), $this->branches->listManagement(self::LIST_LIMIT));
    }

    /** @return array<string, mixed>|null */
    public function getBranch(int $id): ?array
    {
        $branch = $this->branches->findById($id);
        if ($branch === null) {
            return null;
        }

        $branch = $this->decorateBranch($branch);
        $branch['departments'] = $this->departments->listByBranchId($id);
        return $branch;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData([
            'company_id' => '',
            'prefecture_code' => '',
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
        if ((string) ($branch['status'] ?? '') !== 'active') {
            return null;
        }

        $branch = $this->decorateBranch($branch);
        return $this->formData([
            'company_id' => (int) $branch['company_id'],
            'prefecture_code' => (string) $branch['code'],
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

        $input = $validation->input;
        $prefecture = $this->prefectures->find($input->prefectureCode);
        if ($prefecture === null) {
            return $this->failure(null, $validation->values, ['prefecture_code' => 'Select a supported prefecture.']);
        }

        $errors = $this->businessErrors($input->companyId, $prefecture['code'], null);
        if ($errors !== []) {
            return $this->failure(null, $validation->values, $errors);
        }

        $canonical = new BranchInput(
            $input->companyId,
            $prefecture['code'],
            $prefecture['branch_name'],
            $input->city,
            $input->address,
            $input->phone,
        );

        try {
            $now = $this->now();
            $id = $this->branches->insert($canonical, $now, $now);
        } catch (BranchDuplicateException) {
            return $this->failure(null, $validation->values, ['prefecture_code' => 'A branch with this prefecture already exists for the selected company.']);
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

        $validation = $this->validator->validateMetadata($rawInput);
        $values = $this->editValues($current, $validation->values);
        if (!$validation->isValid()) {
            return $this->failure($id, $values, $validation->errors);
        }

        if ((string) ($current['status'] ?? '') !== 'active') {
            return $this->failure(null, [], []);
        }
        if (!$this->branches->updateMetadata($id, $validation->input, $this->now())) {
            return $this->failure(null, [], []);
        }

        return ['success' => true, 'id' => $id, 'values' => $values, 'errors' => []];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        $branch = $this->branches->findById($id);
        return $branch !== null && (string) ($branch['status'] ?? '') === 'active' ? $branch : null;
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
            'prefectures' => $this->prefectures->all(),
            'branch' => $branch,
        ];
    }

    /** @param array<string, mixed> $current @param array<string, mixed> $metadata */
    private function editValues(array $current, array $metadata): array
    {
        return [
            'company_id' => (int) $current['company_id'],
            'prefecture_code' => (string) $current['code'],
            'code' => (string) $current['code'],
            'name' => (string) $current['name'],
            'city' => $metadata['city'] ?? (string) $current['city'],
            'address' => $metadata['address'] ?? (string) $current['address'],
            'phone' => $metadata['phone'] ?? (string) $current['phone'],
        ];
    }

    /** @return array<string, mixed> */
    private function decorateBranch(array $branch): array
    {
        $prefecture = $this->prefectures->find((string) ($branch['code'] ?? ''));
        $branch['prefecture_code'] = (string) ($branch['code'] ?? '');
        $branch['prefecture_label_ja'] = $prefecture['name'] ?? null;
        $branch['prefecture_label_en'] = $prefecture['label_en'] ?? null;
        return $branch;
    }

    /** @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(?int $id, array $values, array $errors): array
    {
        return ['success' => false, 'id' => $id, 'values' => $values, 'errors' => $errors];
    }

    /** @return array<string, string> */
    private function businessErrors(int $companyId, string $code, ?int $exceptId): array
    {
        $errors = [];
        if ($this->branches->findCompanyById($companyId) === null) {
            $errors['company_id'] = 'Select an existing company.';
        }

        if ($this->branches->codeExists($companyId, $code, $exceptId)) {
            $errors['prefecture_code'] = 'A branch with this prefecture already exists for the selected company.';
        }

        return $errors;
    }

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
