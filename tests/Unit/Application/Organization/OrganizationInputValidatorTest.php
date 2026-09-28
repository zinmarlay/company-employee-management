<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Organization;

use App\Application\Validation\BranchInputValidator;
use App\Application\Validation\DepartmentInputValidator;
use PHPUnit\Framework\TestCase;

final class OrganizationInputValidatorTest extends TestCase
{
    public function testBranchValidatorNormalizesAValidBranchCreationRequest(): void
    {
        $result = (new BranchInputValidator())->validate([
            'company_id' => '1',
            'prefecture_code' => ' tokyo ',
            'city' => '新宿区',
            'address' => '東京都千代田区1-1-1',
            'phone' => '03-1234-5678',
        ]);

        self::assertTrue($result->isValid());
        self::assertSame(1, $result->input?->companyId);
        self::assertSame('tokyo', $result->input?->prefectureCode);
        self::assertSame('新宿区', $result->input?->city);
    }

    public function testBranchValidatorRejectsMissingAndOverLengthFields(): void
    {
        $result = (new BranchInputValidator())->validate([
            'company_id' => '',
            'prefecture_code' => str_repeat('x', 31),
            'city' => '',
            'address' => '',
            'phone' => '',
        ]);

        self::assertFalse($result->isValid());
        self::assertArrayHasKey('company_id', $result->errors);
        self::assertSame('This field must be 30 characters or fewer.', $result->errors['prefecture_code']);
    }

    public function testDepartmentValidatorPreservesOptionalDescriptionAsNullWhenBlank(): void
    {
        $result = (new DepartmentInputValidator())->validate([
            'branch_id' => '2',
            'code' => 'DEV',
            'name' => '開発部',
            'description' => '  ',
        ]);

        self::assertTrue($result->isValid());
        self::assertNull($result->input?->description);
    }

    public function testDepartmentValidatorRejectsNonScalarDescription(): void
    {
        $result = (new DepartmentInputValidator())->validate([
            'branch_id' => '2',
            'code' => 'DEV',
            'name' => 'Development',
            'description' => ['unexpected'],
        ]);

        self::assertFalse($result->isValid());
        self::assertSame('Enter a text value.', $result->errors['description']);
    }
}
