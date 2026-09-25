<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Database\LazyPdoConnection;
use App\Domain\Employee\EmployeeCodeAllocatorInterface;
use App\Domain\Employee\EmployeeCodeSequenceExhaustedException;
use LogicException;

final class PdoEmployeeCodeAllocator implements EmployeeCodeAllocatorInterface
{
    private const SEQUENCE_NAME = 'employee_code';
    private const MAX_VALUE = 999999;

    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function allocate(): string
    {
        $pdo = $this->connection->get();

        if (!$pdo->inTransaction()) {
            throw new LogicException('Employee-code allocation requires an active transaction.');
        }

        $statement = $pdo->prepare(
            'SELECT next_value FROM employee_code_sequences '
            . 'WHERE sequence_name = :sequence_name FOR UPDATE',
        );
        $statement->execute(['sequence_name' => self::SEQUENCE_NAME]);
        $value = $statement->fetchColumn();

        if ($value === false) {
            throw new LogicException('Employee-code sequence state is missing.');
        }

        $nextValue = (int) $value;

        if ($nextValue < 1 || $nextValue > self::MAX_VALUE) {
            throw new EmployeeCodeSequenceExhaustedException(
                'The employee-code sequence has no available values.',
            );
        }

        $update = $pdo->prepare(
            'UPDATE employee_code_sequences SET next_value = :next_value '
            . 'WHERE sequence_name = :sequence_name',
        );
        $update->execute([
            'next_value' => $nextValue + 1,
            'sequence_name' => self::SEQUENCE_NAME,
        ]);

        if ($update->rowCount() !== 1) {
            throw new LogicException('Employee-code sequence state could not be advanced.');
        }

        return 'EMP' . str_pad((string) $nextValue, 6, '0', STR_PAD_LEFT);
    }
}
