<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Application\Organization\OrganizationDisplayNameResolver;
use PHPUnit\Framework\TestCase;

final class OrganizationDisplayNameResolverTest extends TestCase
{
    public function testCatalogBackedNamesAreLocalizedWithoutChangingIdentity(): void
    {
        $resolver = new OrganizationDisplayNameResolver();

        $branch = $resolver->branch(['id' => 7, 'code' => 'TOKYO', 'name' => '東京支店'], 'en');
        self::assertSame(7, $branch['id']);
        self::assertSame('TOKYO', $branch['code']);
        self::assertSame('東京支店', $branch['name']);
        self::assertSame('Tokyo Branch', $branch['display_name']);

        $department = $resolver->department([
            'id' => 8,
            'branch_id' => 7,
            'branch_code' => 'TOKYO',
            'branch_name' => '東京支店',
            'code' => 'DEV',
            'name' => '開発部',
        ], 'en');
        self::assertSame('Development', $department['display_name']);
        self::assertSame('Tokyo Branch · Development', $department['display_label']);
    }

    public function testUnknownCodesFallBackToPersistedNamesInBothLocales(): void
    {
        $resolver = new OrganizationDisplayNameResolver();

        self::assertSame('横浜支店', $resolver->branchName('YOKOHAMA_CUSTOM', '横浜支店', 'en'));
        self::assertSame('横浜支店', $resolver->branchName('YOKOHAMA_CUSTOM', '横浜支店', 'ja'));
        self::assertSame('特別部門', $resolver->departmentName('CUSTOM', '特別部門', 'en'));
        self::assertSame('特別部門', $resolver->departmentName('CUSTOM', '特別部門', 'ja'));
    }
}
