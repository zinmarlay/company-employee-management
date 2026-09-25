<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\DispatchCompanyInputValidator;
use App\Application\Validation\DispatchContractInputValidator;
use PHPUnit\Framework\TestCase;

final class DispatchInputValidatorTest extends TestCase
{
    public function testCompanyValidatorTrimsOptionalsAndIgnoresUnexpectedFields(): void
    {
        $result = (new DispatchCompanyInputValidator())->validate([
            'code' => '  PARTNER-01 ',
            'name' => '  Staffing Partner ',
            'email' => ' partner@example.test ',
            'phone' => '',
            'address' => ' Tokyo ',
            'status' => 'inactive',
        ]);

        self::assertTrue($result->isValid());
        self::assertSame('PARTNER-01', $result->input?->code);
        self::assertSame('partner@example.test', $result->input?->email);
        self::assertNull($result->input?->phone);
        self::assertArrayNotHasKey('status', $result->values);
    }

    public function testContractValidatorRejectsImpossibleAndReversedDates(): void
    {
        $validator = new DispatchContractInputValidator();
        $invalidCalendar = $validator->validate([
            'employee_id' => '1',
            'dispatch_company_id' => '1',
            'start_date' => '2026-02-30',
            'end_date' => '2026-03-01',
        ]);
        self::assertArrayHasKey('start_date', $invalidCalendar->errors);

        $reversed = $validator->validate([
            'employee_id' => '1',
            'dispatch_company_id' => '1',
            'start_date' => '2026-10-01',
            'end_date' => '2026-09-30',
        ]);
        self::assertArrayHasKey('start_date', $reversed->errors);
        self::assertArrayHasKey('end_date', $reversed->errors);
    }
}
