<?php

declare(strict_types=1);

namespace App\Application\Support;

use DateTimeImmutable;

interface Clock
{
    public function nowUtc(): DateTimeImmutable;
}
