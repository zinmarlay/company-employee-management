<?php

declare(strict_types=1);

namespace App\Database\Migration;

use PDO;
use Throwable;

final class MigrationRunner
{
    private const TRACKING_TABLE = 'schema_migrations';

    public function __construct(
        private readonly PDO $pdo,
        private readonly MigrationDiscovery $discovery,
    ) {
    }

    public function migrate(): int
    {
        $this->ensureTrackingTable();
        $definitions = $this->discovery->discover();
        $applied = $this->appliedNames();
        $pending = array_values(array_filter(
            $definitions,
            static fn (MigrationDefinition $definition): bool => !isset($applied[$definition->name()]),
        ));

        if ($pending === []) {
            return 0;
        }

        $batch = $this->nextBatch();

        foreach ($pending as $definition) {
            $this->runUp($definition, $batch);
        }

        return count($pending);
    }

    /**
     * @return array<int, array{name: string, version: string, applied: bool, batch: int|null, applied_at: string|null}>
     */
    public function status(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->appliedRows();
        $status = [];

        foreach ($this->discovery->discover() as $definition) {
            $row = $applied[$definition->name()] ?? null;
            $status[] = [
                'name' => $definition->name(),
                'version' => $definition->version(),
                'applied' => $row !== null,
                'batch' => $row['batch'] ?? null,
                'applied_at' => $row['applied_at'] ?? null,
            ];
        }

        return $status;
    }

    public function rollback(): int
    {
        $this->ensureTrackingTable();
        $rows = $this->pdo->query(
            'SELECT migration FROM schema_migrations ORDER BY batch DESC, migration DESC',
        )->fetchAll();

        if ($rows === []) {
            return 0;
        }

        $latestBatch = (int) $this->pdo->query(
            'SELECT MAX(batch) FROM schema_migrations',
        )->fetchColumn();
        $definitions = [];

        foreach ($this->discovery->discover() as $definition) {
            $definitions[$definition->name()] = $definition;
        }

        $rolledBack = 0;

        foreach ($rows as $row) {
            if ((int) $this->rowBatch($row['migration']) !== $latestBatch) {
                continue;
            }

            $name = (string) $row['migration'];

            if (!isset($definitions[$name])) {
                throw new MigrationException(sprintf('Applied migration is missing from disk: %s', $name));
            }

            $this->runDown($definitions[$name]);
            $rolledBack++;
        }

        return $rolledBack;
    }

    private function runUp(MigrationDefinition $definition, int $batch): void
    {
        try {
            $this->pdo->beginTransaction();
            $definition->migration()->up($this->pdo);
            $statement = $this->pdo->prepare(
                'INSERT INTO schema_migrations (migration, batch, applied_at) '
                . 'VALUES (:migration, :batch, CURRENT_TIMESTAMP)',
            );
            $statement->execute([
                'migration' => $definition->name(),
                'batch' => $batch,
            ]);

            // MySQL DDL may implicitly commit the transaction. Only commit when
            // the driver still reports an active transaction.
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new MigrationException(sprintf('Migration failed: %s', $definition->name()), $exception);
        }
    }

    private function runDown(MigrationDefinition $definition): void
    {
        try {
            $this->pdo->beginTransaction();
            $definition->migration()->down($this->pdo);
            $statement = $this->pdo->prepare('DELETE FROM schema_migrations WHERE migration = :migration');
            $statement->execute(['migration' => $definition->name()]);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new MigrationException(sprintf('Rollback failed: %s', $definition->name()), $exception);
        }
    }

    private function ensureTrackingTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations ('
            . 'migration VARCHAR(191) NOT NULL PRIMARY KEY,'
            . 'batch INT UNSIGNED NOT NULL,'
            . 'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        );
    }

    /** @return array<string, bool> */
    private function appliedNames(): array
    {
        $names = [];

        foreach ($this->pdo->query('SELECT migration FROM schema_migrations')->fetchAll() as $row) {
            $names[(string) $row['migration']] = true;
        }

        return $names;
    }

    /**
     * @return array<string, array{batch: int, applied_at: string}>
     */
    private function appliedRows(): array
    {
        $rows = [];

        foreach ($this->pdo->query('SELECT migration, batch, applied_at FROM schema_migrations')->fetchAll() as $row) {
            $rows[(string) $row['migration']] = [
                'batch' => (int) $row['batch'],
                'applied_at' => (string) $row['applied_at'],
            ];
        }

        return $rows;
    }

    private function nextBatch(): int
    {
        return ((int) $this->pdo->query('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations')->fetchColumn()) + 1;
    }

    private function rowBatch(string $name): int
    {
        $statement = $this->pdo->prepare('SELECT batch FROM schema_migrations WHERE migration = :migration');
        $statement->execute(['migration' => $name]);

        return (int) $statement->fetchColumn();
    }
}
