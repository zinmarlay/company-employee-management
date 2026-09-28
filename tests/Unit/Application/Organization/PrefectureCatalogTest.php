<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Domain\Organization\PrefectureCatalog;
use PHPUnit\Framework\TestCase;

final class PrefectureCatalogTest extends TestCase
{
    public function testCatalogContainsExactlyTheFortySevenPrefectures(): void
    {
        $catalog = new PrefectureCatalog();
        $entries = $catalog->all();

        self::assertCount(47, $entries);
        self::assertCount(47, array_unique(array_column($entries, 'code')));
        self::assertSame('TOKYO', $catalog->find('tokyo')['code']);
        self::assertSame('東京支店', $catalog->branchName('TOKYO'));
        self::assertSame('東京都', $catalog->label('TOKYO', 'ja'));
        self::assertSame('Tokyo', $catalog->label('TOKYO', 'en'));
    }

    public function testCatalogRejectsUnsupportedValues(): void
    {
        $catalog = new PrefectureCatalog();

        self::assertFalse($catalog->contains('NOT_A_PREFECTURE'));
        self::assertNull($catalog->find('NOT_A_PREFECTURE'));
        self::assertNull($catalog->branchName('NOT_A_PREFECTURE'));
    }
}
