<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\SystemUserInputValidator;
use PHPUnit\Framework\TestCase;

final class SystemUserInputValidatorTest extends TestCase
{
    public function testCreateNormalizesEmailAndAcceptsAnAdministratorPassword(): void
    {
        $result = (new SystemUserInputValidator())->validate([
            'name' => '  Administrator  ',
            'email' => ' ADMIN@Example.TEST ',
            'role' => 'ADMIN',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
        ], true);

        self::assertTrue($result->isValid());
        self::assertSame('Administrator', $result->input?->name);
        self::assertSame('admin@example.test', $result->input?->email);
        self::assertSame('ADMIN', $result->input?->role);
        self::assertArrayNotHasKey('password', $result->values);
    }

    public function testCreateRejectsShortMismatchedAndInvalidRoleInputWithoutReturningPasswords(): void
    {
        $result = (new SystemUserInputValidator())->validate([
            'name' => 'User',
            'email' => 'user@example.test',
            'role' => 'OWNER',
            'password' => 'short',
            'password_confirmation' => 'different',
        ], true);

        self::assertFalse($result->isValid());
        self::assertArrayHasKey('role', $result->errors);
        self::assertArrayHasKey('password', $result->errors);
        self::assertArrayHasKey('password_confirmation', $result->errors);
        self::assertArrayNotHasKey('password', $result->values);
        self::assertArrayNotHasKey('password_confirmation', $result->values);
    }

    public function testEditAllowsBlankPasswordToKeepTheExistingHash(): void
    {
        $result = (new SystemUserInputValidator())->validate([
            'name' => 'User',
            'email' => 'user@example.test',
            'role' => 'USER',
            'password' => '',
            'password_confirmation' => '',
        ], false);

        self::assertTrue($result->isValid());
        self::assertNull($result->input?->password);
    }
}
