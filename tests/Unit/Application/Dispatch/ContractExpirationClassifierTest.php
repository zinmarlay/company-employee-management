<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Dispatch;

use App\Application\Dispatch\ContractExpirationClassifier;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class ContractExpirationClassifierTest extends TestCase
{
    private ContractExpirationClassifier $classifier;
    private DateTimeImmutable $reference;

    protected function setUp(): void
    {
        $this->classifier = new ContractExpirationClassifier();
        $this->reference = new DateTimeImmutable('2026-10-01 12:00:00', new DateTimeZone('Asia/Tokyo'));
    }

    public function testExpirationBoundariesAreInclusive(): void
    {
        self::assertSame('expired', $this->classifier->classify('2026-09-30', $this->reference));
        self::assertSame('expiring_7', $this->classifier->classify('2026-10-01', $this->reference));
        self::assertSame('expiring_7', $this->classifier->classify('2026-10-08', $this->reference));
        self::assertSame('expiring_30', $this->classifier->classify('2026-10-09', $this->reference));
        self::assertSame('expiring_30', $this->classifier->classify('2026-10-31', $this->reference));
        self::assertSame('normal', $this->classifier->classify('2026-11-01', $this->reference));
    }
}
