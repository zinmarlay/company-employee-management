<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Domain\Organization\DepartmentCatalog;
use App\Domain\Organization\PrefectureCatalog;

final class OrganizationDisplayNameResolver
{
    public function __construct(
        private readonly PrefectureCatalog $prefectures = new PrefectureCatalog(),
        private readonly DepartmentCatalog $departments = new DepartmentCatalog(),
    ) {
    }

    /** @param array<string, mixed> $branch @return array<string, mixed> */
    public function branch(array $branch, string $locale): array
    {
        $branch['display_name'] = $this->branchName(
            (string) ($branch['code'] ?? ''),
            (string) ($branch['name'] ?? ''),
            $locale,
        );
        $prefecture = $this->prefectures->find((string) ($branch['prefecture_code'] ?? $branch['code'] ?? ''));
        if ($prefecture !== null) {
            $branch['prefecture_display_label'] = $locale === 'ja'
                ? (string) ($prefecture['name'] ?? '')
                : (string) ($prefecture['label_en'] ?? '') . ' (' . (string) ($prefecture['name'] ?? '') . ')';
        }

        return $branch;
    }

    /** @param array<string, mixed> $department @return array<string, mixed> */
    public function department(array $department, string $locale): array
    {
        $department['display_name'] = $this->departmentName(
            (string) ($department['code'] ?? ''),
            (string) ($department['name'] ?? ''),
            $locale,
        );
        $department['display_branch_name'] = $this->branchName(
            (string) ($department['branch_code'] ?? ''),
            (string) ($department['branch_name'] ?? ''),
            $locale,
        );
        $department['display_label'] = $this->departmentLabel($department, $locale);

        return $department;
    }

    /** @param array<string, mixed> $employee @return array<string, mixed> */
    public function employee(array $employee, string $locale): array
    {
        $employee['branch_display_name'] = $this->branchName(
            (string) ($employee['branch_code'] ?? ''),
            (string) ($employee['branch_name'] ?? ''),
            $locale,
        );
        $employee['department_display_name'] = ($employee['department_name'] ?? null) === null
            ? null
            : $this->departmentName(
                (string) ($employee['department_code'] ?? ''),
                (string) ($employee['department_name'] ?? ''),
                $locale,
            );

        return $employee;
    }

    /** @param array<string, mixed> $prefecture @return array<string, mixed> */
    public function prefectureOption(array $prefecture, string $locale): array
    {
        $prefecture['display_label'] = $locale === 'ja'
            ? (string) ($prefecture['name'] ?? '')
            : (string) ($prefecture['label_en'] ?? '') . ' (' . (string) ($prefecture['name'] ?? '') . ')';

        return $prefecture;
    }

    /** @param array<string, mixed> $departmentType @return array<string, mixed> */
    public function departmentTypeOption(array $departmentType, string $locale): array
    {
        $label = $this->departmentName(
            (string) ($departmentType['code'] ?? ''),
            (string) ($departmentType['name'] ?? ''),
            $locale,
        );
        $departmentType['display_label'] = $label . ' (' . (string) ($departmentType['code'] ?? '') . ')';

        return $departmentType;
    }

    public function branchName(string $code, string $persistedName, string $locale): string
    {
        return $this->prefectures->branchLabel($code, $locale) ?? $persistedName;
    }

    public function departmentName(string $code, string $persistedName, string $locale): string
    {
        return $this->departments->label($code, $locale) ?? $persistedName;
    }

    /** @param array<string, mixed> $department */
    public function departmentLabel(array $department, string $locale): string
    {
        $branchName = (string) ($department['display_branch_name'] ?? $department['branch_name'] ?? '');
        $departmentName = (string) ($department['display_name'] ?? $department['name'] ?? '');
        $separator = $locale === 'ja' ? '・' : ' · ';

        return $branchName . $separator . $departmentName;
    }
}
