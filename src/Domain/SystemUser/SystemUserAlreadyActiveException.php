<?php

declare(strict_types=1);

namespace App\Domain\SystemUser;

use RuntimeException;

final class SystemUserAlreadyActiveException extends RuntimeException
{
}
