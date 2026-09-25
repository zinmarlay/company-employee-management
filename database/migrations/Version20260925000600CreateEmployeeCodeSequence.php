<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration\MigrationInterface;
use PDO;
use RuntimeException;

final class Version20260925000600CreateEmployeeCodeSequence implements MigrationInterface
{
    private const SEQUENCE_NAME = 'employee_code';
    private const MAX_VALUE = 999999;

    public function up(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, employee_code FROM employees ORDER BY id FOR UPDATE')->fetchAll();
        $targets = [];
        $updates = [];
        $maximum = 0;

        foreach ($rows as $row) {
            $current = (string) $row['employee_code'];
            $target = $this->canonicalCode($current);
            $number = (int) substr($target, 3);

            if (isset($targets[$target]) && $targets[$target] !== (int) $row['id']) {
                throw new RuntimeException(sprintf(
                    'Employee-code migration collision for %s (employee #%d and #%d).',
                    $target,
                    $targets[$target],
                    (int) $row['id'],
                ));
            }

            $targets[$target] = (int) $row['id'];
            $maximum = max($maximum, $number);

            if ($target !== $current) {
                $updates[] = ['id' => (int) $row['id'], 'employee_code' => $target];
            }
        }

        $pdo->exec(
            'CREATE TABLE employee_code_sequences ('
            . 'sequence_name VARCHAR(64) NOT NULL,'
            . 'next_value INT UNSIGNED NOT NULL,'
            . 'PRIMARY KEY (sequence_name)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        $statement = $pdo->prepare('UPDATE employees SET employee_code = :employee_code WHERE id = :id');
        foreach ($updates as $update) {
            $statement->execute($update);
        }

        $nextValue = $maximum === self::MAX_VALUE ? self::MAX_VALUE + 1 : $maximum + 1;
        $sequence = $pdo->prepare(
            'INSERT INTO employee_code_sequences (sequence_name, next_value) '
            . 'VALUES (:sequence_name, :next_value)',
        );
        $sequence->execute([
            'sequence_name' => self::SEQUENCE_NAME,
            'next_value' => $nextValue,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE employee_code_sequences');
    }

    private function canonicalCode(string $code): string
    {
        if (preg_match('/^EMP[0-9]{3}$/D', $code) === 1) {
            return 'EMP' . str_pad(substr($code, 3), 6, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^EMP[0-9]{6}$/D', $code) === 1) {
            $number = (int) substr($code, 3);

            if ($number >= 1 && $number <= self::MAX_VALUE) {
                return $code;
            }
        }

        throw new RuntimeException(sprintf(
            'Unsupported employee code during migration: %s.',
            $code,
        ));
    }
}
