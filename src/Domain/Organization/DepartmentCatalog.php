<?php

declare(strict_types=1);

namespace App\Domain\Organization;

final class DepartmentCatalog
{
    /** @var array<int, array{code: string, name: string, label_en: string}> */
    private const ENTRIES = [
        ['code' => 'DEV', 'name' => '開発部', 'label_en' => 'Development'],
        ['code' => 'SALES', 'name' => '営業部', 'label_en' => 'Sales'],
        ['code' => 'HR', 'name' => '人事部', 'label_en' => 'Human Resources'],
        ['code' => 'GA', 'name' => '総務部', 'label_en' => 'General Affairs'],
        ['code' => 'FIN', 'name' => '経理部', 'label_en' => 'Accounting'],
        ['code' => 'IT', 'name' => '情報システム部', 'label_en' => 'Information Systems'],
        ['code' => 'LEGAL', 'name' => '法務部', 'label_en' => 'Legal'],
        ['code' => 'PR', 'name' => '広報部', 'label_en' => 'Public Relations'],
        ['code' => 'PL', 'name' => '企画部', 'label_en' => 'Planning'],
        ['code' => 'CS', 'name' => 'カスタマーサポート部', 'label_en' => 'Customer Support'],
    ];

    /** @return array<int, array{code: string, name: string, label_en: string}> */
    public function all(): array
    {
        return self::ENTRIES;
    }

    /** @return array{code: string, name: string, label_en: string}|null */
    public function find(string $code): ?array
    {
        $normalized = strtoupper(trim($code));
        foreach (self::ENTRIES as $entry) {
            if ($entry['code'] === $normalized) {
                return $entry;
            }
        }

        return null;
    }

    public function contains(string $code): bool
    {
        return $this->find($code) !== null;
    }

    public function departmentName(string $code): ?string
    {
        return $this->find($code)['name'] ?? null;
    }

    public function label(string $code, string $locale): ?string
    {
        $entry = $this->find($code);
        if ($entry === null) {
            return null;
        }

        return $locale === 'ja' ? $entry['name'] : $entry['label_en'];
    }
}
