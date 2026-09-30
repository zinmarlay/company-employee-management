<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\DepartmentInputValidator;
use PHPUnit\Framework\TestCase;

final class DepartmentInputValidatorTest extends TestCase
{
    public function testDescriptionIsBounded(): void
    {
        $result = (new DepartmentInputValidator())->validateMetadata([
            'description' => str_repeat('x', 5001),
        ]);

        self::assertFalse($result->isValid());
        self::assertSame('This field must be 5000 characters or fewer.', $result->errors['description']);
    }
}
