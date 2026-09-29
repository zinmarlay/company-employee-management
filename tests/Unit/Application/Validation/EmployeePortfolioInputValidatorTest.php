<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\EmployeeCertificationInputValidator;
use App\Application\Validation\EmployeeProjectInputValidator;
use App\Application\Validation\EmployeeSkillInputValidator;
use PHPUnit\Framework\TestCase;

final class EmployeePortfolioInputValidatorTest extends TestCase
{
    public function testSkillValidatorNormalizesAndAcceptsApprovedValues(): void
    {
        $result = (new EmployeeSkillInputValidator())->validate([
            'skill_name' => "  PHP\n  ",
            'proficiency' => 'advanced',
            'years_experience' => '5.5',
            'notes' => ' Backend ',
        ]);

        self::assertTrue($result->isValid());
        self::assertSame('PHP', $result->input?->skillName);
        self::assertSame(5.5, $result->input?->yearsExperience);
        self::assertSame('Backend', $result->input?->notes);
    }

    public function testSkillValidatorRejectsInvalidProficiencyAndYears(): void
    {
        $result = (new EmployeeSkillInputValidator())->validate([
            'skill_name' => 'PHP',
            'proficiency' => '90%',
            'years_experience' => '100.0',
        ]);

        self::assertFalse($result->isValid());
        self::assertArrayHasKey('proficiency', $result->errors);
        self::assertArrayHasKey('years_experience', $result->errors);
    }

    public function testProjectValidatorAllowsOverlappingAndOngoingProjects(): void
    {
        $validator = new EmployeeProjectInputValidator();
        $first = $validator->validate([
            'project_name' => 'Migration',
            'role' => 'Engineer',
            'start_date' => '2026-01-01',
            'end_date' => '',
            'description' => '',
            'responsibilities' => '',
            'technologies' => 'PHP, MySQL',
        ]);
        $second = $validator->validate([
            'project_name' => 'Support',
            'role' => 'Engineer',
            'start_date' => '2026-02-01',
            'end_date' => '2026-03-01',
        ]);

        self::assertTrue($first->isValid());
        self::assertNull($first->input?->endDate);
        self::assertTrue($second->isValid());
    }

    public function testProjectValidatorRejectsReversedDates(): void
    {
        $result = (new EmployeeProjectInputValidator())->validate([
            'project_name' => 'Migration',
            'role' => 'Engineer',
            'start_date' => '2026-03-01',
            'end_date' => '2026-02-01',
        ]);

        self::assertFalse($result->isValid());
        self::assertSame('Start date must be on or before end date.', $result->errors['start_date']);
        self::assertSame('End date must be on or after start date.', $result->errors['end_date']);
    }

    public function testCertificationValidatorAcceptsRenewalDatesAndOptionalFields(): void
    {
        $result = (new EmployeeCertificationInputValidator())->validate([
            'certification_name' => 'AWS Certified Solutions Architect',
            'issuing_organization' => 'AWS',
            'obtained_date' => '2024-01-01',
            'expiration_date' => '2027-01-01',
            'credential_identifier' => '',
            'notes' => '',
        ]);

        self::assertTrue($result->isValid());
        self::assertNull($result->input?->credentialIdentifier);
        self::assertNull($result->input?->notes);
    }

    public function testCertificationValidatorRejectsExpirationBeforeObtainedDate(): void
    {
        $result = (new EmployeeCertificationInputValidator())->validate([
            'certification_name' => 'JLPT',
            'issuing_organization' => 'JEES',
            'obtained_date' => '2026-06-01',
            'expiration_date' => '2026-05-01',
        ]);

        self::assertFalse($result->isValid());
        self::assertArrayHasKey('obtained_date', $result->errors);
        self::assertArrayHasKey('expiration_date', $result->errors);
    }
}

