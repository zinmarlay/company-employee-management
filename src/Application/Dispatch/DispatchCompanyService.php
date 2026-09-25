<?php

declare(strict_types=1);

namespace App\Application\Dispatch;

use App\Application\DTO\DispatchCompanyInput;
use App\Application\Support\Clock;
use App\Application\Validation\DispatchCompanyInputValidator;
use App\Domain\Dispatch\DispatchCompanyDuplicateException;
use App\Domain\Dispatch\DispatchCompanyRepositoryInterface;
use App\Domain\Dispatch\DispatchContractRepositoryInterface;

final class DispatchCompanyService
{
    private const LIST_LIMIT = 200;

    public function __construct(
        private readonly DispatchCompanyRepositoryInterface $companies,
        private readonly DispatchContractRepositoryInterface $contracts,
        private readonly DispatchCompanyInputValidator $validator,
        private readonly Clock $clock,
        private readonly ContractExpirationClassifier $expiration,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listCompanies(): array
    {
        return $this->companies->listBasic(self::LIST_LIMIT);
    }

    /** @return array<string, mixed>|null */
    public function getCompany(int $id): ?array
    {
        $company = $this->companies->findById($id);
        if ($company === null) {
            return null;
        }

        $company['contracts'] = array_map(
            fn (array $contract): array => $this->withExpiration($contract),
            $this->contracts->findByCompanyId($id),
        );

        return $company;
    }

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return $this->formData([
            'code' => '',
            'name' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
        ], [], null);
    }

    /** @return array<string, mixed>|null */
    public function editForm(int $id): ?array
    {
        $company = $this->companies->findById($id);
        if ($company === null) {
            return null;
        }

        return $this->formData([
            'code' => (string) $company['code'],
            'name' => (string) $company['name'],
            'phone' => $company['phone'] ?? '',
            'email' => $company['email'] ?? '',
            'address' => $company['address'] ?? '',
        ], [], $company);
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function createCompany(array $rawInput): array
    {
        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return $this->failure($validation->values, $validation->errors);
        }

        try {
            $now = $this->now();
            $id = $this->companies->insert($validation->input, $now, $now);
        } catch (DispatchCompanyDuplicateException $exception) {
            return $this->failure($validation->values, [$exception->field => 'A dispatch company with this code already exists.']);
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @param array<string, mixed> $rawInput @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    public function updateCompany(int $id, array $rawInput): array
    {
        $current = $this->companies->findById($id);
        if ($current === null) {
            return $this->failure([], []);
        }

        $validation = $this->validator->validate($rawInput);
        if (!$validation->isValid()) {
            return ['success' => false, 'id' => $id, 'values' => $validation->values, 'errors' => $validation->errors];
        }

        try {
            $this->companies->update($id, $validation->input, $this->now());
        } catch (DispatchCompanyDuplicateException $exception) {
            return ['success' => false, 'id' => $id, 'values' => $validation->values, 'errors' => [$exception->field => 'A dispatch company with this code already exists.']];
        }

        return ['success' => true, 'id' => $id, 'values' => $validation->values, 'errors' => []];
    }

    /** @return array<string, mixed>|null */
    public function deactivationForm(int $id): ?array
    {
        return $this->companies->findById($id);
    }

    /** @return array{status: string, company: array<string, mixed>|null} */
    public function deactivateCompany(int $id): array
    {
        $company = $this->companies->findById($id);
        if ($company === null) {
            return ['status' => 'missing', 'company' => null];
        }

        if ((string) $company['status'] !== 'active') {
            return ['status' => 'already-inactive', 'company' => $company];
        }

        $this->companies->deactivate($id, $this->now());
        return ['status' => 'deactivated', 'company' => $this->companies->findById($id)];
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors @param array<string, mixed>|null $current @return array<string, mixed> */
    private function formData(array $values, array $errors, ?array $current): array
    {
        return ['values' => $values, 'errors' => $errors, 'company' => $current];
    }

    /** @return array{success: bool, id: int|null, values: array<string, mixed>, errors: array<string, string>} */
    private function failure(array $values, array $errors): array
    {
        return ['success' => false, 'id' => null, 'values' => $values, 'errors' => $errors];
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

    private function now(): string
    {
        return $this->clock->nowUtc()->format('Y-m-d H:i:s');
    }
}
