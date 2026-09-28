<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Domain\Organization\DepartmentCatalog;
use PHPUnit\Framework\TestCase;

final class DepartmentCatalogTest extends TestCase
{
    public function testCatalogContainsTheApprovedTenDepartmentTypes(): void
    {
        $catalog = new DepartmentCatalog();
        $entries = $catalog->all();

        self::assertCount(10, $entries);
        self::assertCount(10, array_unique(array_column($entries, 'code')));
        self::assertSame('開発部', $catalog->departmentName('dev'));
        self::assertSame('Development', $catalog->label('DEV', 'en'));
        self::assertSame('営業部', $catalog->label('SALES', 'ja'));
    }

    public function testCatalogRejectsUnsupportedDepartmentTypes(): void
    {
        $catalog = new DepartmentCatalog();

        self::assertFalse($catalog->contains('HACKED'));
        self::assertNull($catalog->find('HACKED'));
        self::assertNull($catalog->departmentName('HACKED'));
    }
}
