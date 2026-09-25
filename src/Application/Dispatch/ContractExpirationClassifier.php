<?php

declare(strict_types=1);

namespace App\Application\Dispatch;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class ContractExpirationClassifier
{
    public const EXPIRED = 'expired';
    public const EXPIRING_7 = 'expiring_7';
    public const EXPIRING_30 = 'expiring_30';
    public const NORMAL = 'normal';

    public function classify(string $endDate, DateTimeImmutable $referenceDate): string
    {
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($end === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $end->format('Y-m-d') !== $endDate) {
            throw new InvalidArgumentException('Contract end date must be a valid Y-m-d date.');
        }

        $reference = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $referenceDate->format('Y-m-d'),
            new DateTimeZone('UTC'),
        );
        $seven = $reference->modify('+7 days');
        $thirty = $reference->modify('+30 days');

        if ($end < $reference) {
            return self::EXPIRED;
        }
        if ($end <= $seven) {
            return self::EXPIRING_7;
        }
        if ($end <= $thirty) {
            return self::EXPIRING_30;
        }

        return self::NORMAL;
    }
}
