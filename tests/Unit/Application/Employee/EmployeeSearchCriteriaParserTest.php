<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Employee;

use App\Application\Employee\EmployeeSearchCriteriaParser;
use PHPUnit\Framework\TestCase;

final class EmployeeSearchCriteriaParserTest extends TestCase
{
    public function testItNormalizesApprovedCriteriaAndQueryState(): void
    {
        $criteria = (new EmployeeSearchCriteriaParser())->parse([
            'keyword' => '  山田  ',
            'branch_id' => '2',
            'department_id' => '3',
            'employee_type' => 'dispatched',
            'status' => 'inactive',
            'sort' => 'name',
            'direction' => 'desc',
            'page' => '4',
        ]);

        self::assertSame('山田', $criteria->keyword);
        self::assertSame(2, $criteria->branchId);
        self::assertSame(3, $criteria->departmentId);
        self::assertSame('dispatched', $criteria->employeeType);
        self::assertSame('inactive', $criteria->status);
        self::assertSame('name', $criteria->sort);
        self::assertSame('desc', $criteria->direction);
        self::assertSame(4, $criteria->page);
        self::assertSame(20, $criteria->perPage);
        self::assertSame(4, $criteria->queryParameters()['page']);
    }

    public function testMalformedValuesFallBackSafely(): void
    {
        $criteria = (new EmployeeSearchCriteriaParser())->parse([
            'keyword' => ['unexpected'],
            'branch_id' => '0',
            'department_id' => '-2',
            'employee_type' => 'regular',
            'status' => 'archived',
            'sort' => 'raw_sql',
            'direction' => 'sideways',
            'page' => '-1',
        ]);

        self::assertNull($criteria->keyword);
        self::assertNull($criteria->branchId);
        self::assertNull($criteria->departmentId);
        self::assertNull($criteria->employeeType);
        self::assertNull($criteria->status);
        self::assertSame('employee_code', $criteria->sort);
        self::assertSame('asc', $criteria->direction);
        self::assertSame(1, $criteria->page);
    }
}
