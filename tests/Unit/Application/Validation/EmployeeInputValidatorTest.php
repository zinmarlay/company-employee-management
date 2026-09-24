<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\EmployeeInputValidator;
use PHPUnit\Framework\TestCase;

final class EmployeeInputValidatorTest extends TestCase
{
    public function testValidInputIsNormalizedIntoEmployeeInput(): void
    {
        $result = (new EmployeeInputValidator())->validate([
            'employee_code' => ' EMP-001 ',
            'first_name' => '太郎',
            'last_name' => '山田',
            'first_name_kana' => 'タロウ',
            'last_name_kana' => 'ヤマダ',
            'email' => ' taro@example.test ',
            'phone' => ' 03-0000-0000 ',
            'position_title' => ' Engineer ',
            'branch_id' => '10',
            'department_id' => '',
            'employee_type' => 'permanent',
            'hire_date' => '2026-09-24',
        ]);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->errors);
        self::assertNotNull($result->input);
        self::assertSame('EMP-001', $result->input->employeeCode);
        self::assertSame('taro@example.test', $result->input->email);
        self::assertSame(10, $result->input->branchId);
        self::assertNull($result->input->departmentId);
    }

    public function testInvalidInputReturnsFieldErrorsAndSafeSubmittedValues(): void
    {
        $result = (new EmployeeInputValidator())->validate([
            'employee_code' => ['unexpected' => 'array'],
            'first_name' => '',
            'last_name' => 'Yamada',
            'first_name_kana' => 'タロウ',
            'last_name_kana' => 'ヤマダ',
            'email' => 'not-an-email',
            'branch_id' => '0',
            'department_id' => ['bad'],
            'employee_type' => 'contractor',
            'hire_date' => '2026-02-30',
        ]);

        self::assertFalse($result->isValid());
        self::assertNull($result->input);
        self::assertArrayHasKey('employee_code', $result->errors);
        self::assertArrayHasKey('first_name', $result->errors);
        self::assertArrayHasKey('email', $result->errors);
        self::assertArrayHasKey('branch_id', $result->errors);
        self::assertArrayHasKey('department_id', $result->errors);
        self::assertArrayHasKey('employee_type', $result->errors);
        self::assertArrayHasKey('hire_date', $result->errors);
        self::assertSame('', $result->values['employee_code']);
        self::assertSame('', $result->values['department_id']);
    }
}
