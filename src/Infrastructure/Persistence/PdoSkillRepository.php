<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\EmployeeSkillInput;
use App\Database\LazyPdoConnection;
use App\Domain\Employee\PortfolioDuplicateException;
use App\Domain\Employee\PortfolioWriteResult;
use App\Domain\Employee\SkillRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

final class PdoSkillRepository implements SkillRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listForEmployee(int $employeeId, bool $includeArchived = true): array
    {
        $sql = 'SELECT es.id, es.employee_id, es.skill_id, s.name AS skill_name, '
            . 'es.proficiency, es.years_experience, es.notes, es.status, es.archived_at, '
            . 'es.created_at, es.updated_at '
            . 'FROM employee_skills es INNER JOIN skills s ON s.id = es.skill_id '
            . 'WHERE es.employee_id = :employee_id';
        if (!$includeArchived) {
            $sql .= " AND es.status = 'active'";
        }
        $sql .= ' ORDER BY es.status ASC, s.name ASC, es.id ASC';

        $statement = $this->connection->get()->prepare($sql);
        $statement->execute(['employee_id' => $employeeId]);

        return $statement->fetchAll();
    }

    public function findAssignmentForEmployee(int $employeeId, int $assignmentId): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT es.id, es.employee_id, es.skill_id, s.name AS skill_name, '
            . 'es.proficiency, es.years_experience, es.notes, es.status, es.archived_at, '
            . 'es.created_at, es.updated_at '
            . 'FROM employee_skills es INNER JOIN skills s ON s.id = es.skill_id '
            . 'WHERE es.id = :id AND es.employee_id = :employee_id',
        );
        $statement->execute(['id' => $assignmentId, 'employee_id' => $employeeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findSkillByName(string $name): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, name, created_at, updated_at FROM skills WHERE name = :name LIMIT 1',
        );
        $statement->execute(['name' => $name]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function assign(int $employeeId, EmployeeSkillInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $employeeStatus = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($employeeStatus === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($employeeStatus !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }

            $skillId = $this->findOrCreateSkill($pdo, $input->skillName, $createdAt, $updatedAt);
            $association = $this->lockedAssociation($pdo, $employeeId, $skillId);

            if ($association !== null && (string) $association['status'] === 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('duplicate', (int) $association['id']);
            }

            if ($association !== null) {
                $statement = $pdo->prepare(
                    "UPDATE employee_skills SET proficiency = :proficiency, years_experience = :years_experience, "
                    . "notes = :notes, status = 'active', archived_at = NULL, updated_at = :updated_at "
                    . 'WHERE id = :id AND employee_id = :employee_id',
                );
                $statement->execute([
                    'id' => (int) $association['id'],
                    'employee_id' => $employeeId,
                    'proficiency' => $input->proficiency,
                    'years_experience' => $input->yearsExperience,
                    'notes' => $input->notes,
                    'updated_at' => $updatedAt,
                ]);
                $id = (int) $association['id'];
                $pdo->commit();
                return new PortfolioWriteResult('restored', $id);
            }

            $statement = $pdo->prepare(
                'INSERT INTO employee_skills '
                . '(employee_id, skill_id, proficiency, years_experience, notes, status, archived_at, created_at, updated_at) '
                . "VALUES (:employee_id, :skill_id, :proficiency, :years_experience, :notes, 'active', NULL, :created_at, :updated_at)",
            );
            $statement->execute([
                'employee_id' => $employeeId,
                'skill_id' => $skillId,
                'proficiency' => $input->proficiency,
                'years_experience' => $input->yearsExperience,
                'notes' => $input->notes,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();

            return new PortfolioWriteResult('created', $id);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function updateAssignment(int $employeeId, int $assignmentId, EmployeeSkillInput $input, string $updatedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $employeeStatus = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($employeeStatus === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($employeeStatus !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }

            $current = $this->lockedAssignment($pdo, $employeeId, $assignmentId);
            if ($current === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $current['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('archived', $assignmentId);
            }

            $skillId = $this->findOrCreateSkill($pdo, $input->skillName, $updatedAt, $updatedAt);
            if ($skillId !== (int) $current['skill_id']) {
                $target = $this->lockedAssociation($pdo, $employeeId, $skillId);
                if ($target !== null) {
                    $pdo->rollBack();
                    return new PortfolioWriteResult(
                        (string) $target['status'] === 'archived' ? 'duplicate' : 'duplicate',
                        (int) $target['id'],
                    );
                }
            }

            $statement = $pdo->prepare(
                'UPDATE employee_skills SET skill_id = :skill_id, proficiency = :proficiency, '
                . 'years_experience = :years_experience, notes = :notes, updated_at = :updated_at '
                . 'WHERE id = :id AND employee_id = :employee_id AND status = \'active\'',
            );
            $statement->execute([
                'id' => $assignmentId,
                'employee_id' => $employeeId,
                'skill_id' => $skillId,
                'proficiency' => $input->proficiency,
                'years_experience' => $input->yearsExperience,
                'notes' => $input->notes,
                'updated_at' => $updatedAt,
            ]);
            $pdo->commit();

            return new PortfolioWriteResult('updated', $assignmentId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function archiveAssignment(int $employeeId, int $assignmentId, string $archivedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();

        try {
            $employeeStatus = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($employeeStatus === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($employeeStatus !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }

            $assignment = $this->lockedAssignment($pdo, $employeeId, $assignmentId);
            if ($assignment === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $assignment['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('already-archived', $assignmentId);
            }

            $statement = $pdo->prepare(
                "UPDATE employee_skills SET status = 'archived', archived_at = :archived_at, updated_at = :updated_at "
                . 'WHERE id = :id AND employee_id = :employee_id AND status = \'active\'',
            );
            $statement->execute([
                'id' => $assignmentId,
                'employee_id' => $employeeId,
                'archived_at' => $archivedAt,
                'updated_at' => $archivedAt,
            ]);
            $pdo->commit();

            return new PortfolioWriteResult('archived', $assignmentId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function countActiveForEmployee(int $employeeId): int
    {
        $statement = $this->connection->get()->prepare(
            "SELECT COUNT(*) FROM employee_skills WHERE employee_id = :employee_id AND status = 'active'",
        );
        $statement->execute(['employee_id' => $employeeId]);

        return (int) $statement->fetchColumn();
    }

    private function findOrCreateSkill(PDO $pdo, string $name, string $createdAt, string $updatedAt): int
    {
        $statement = $pdo->prepare('SELECT id FROM skills WHERE name = :name LIMIT 1 FOR UPDATE');
        $statement->execute(['name' => $name]);
        $id = $statement->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        try {
            $insert = $pdo->prepare(
                'INSERT INTO skills (name, created_at, updated_at) VALUES (:name, :created_at, :updated_at)',
            );
            $insert->execute(['name' => $name, 'created_at' => $createdAt, 'updated_at' => $updatedAt]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $exception) {
            if (!$this->isDuplicate($exception)) {
                throw $exception;
            }

            $statement = $pdo->prepare('SELECT id FROM skills WHERE name = :name LIMIT 1 FOR UPDATE');
            $statement->execute(['name' => $name]);
            $id = $statement->fetchColumn();
            if ($id === false) {
                throw $exception;
            }
            return (int) $id;
        }
    }

    /** @return array<string,mixed>|null */
    private function lockedAssignment(PDO $pdo, int $employeeId, int $assignmentId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT es.id, es.skill_id, es.status, es.created_at '
            . 'FROM employee_skills es WHERE es.id = :id AND es.employee_id = :employee_id FOR UPDATE',
        );
        $statement->execute(['id' => $assignmentId, 'employee_id' => $employeeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string,mixed>|null */
    private function lockedAssociation(PDO $pdo, int $employeeId, int $skillId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, status FROM employee_skills '
            . 'WHERE employee_id = :employee_id AND skill_id = :skill_id FOR UPDATE',
        );
        $statement->execute(['employee_id' => $employeeId, 'skill_id' => $skillId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    private function lockedEmployeeStatus(PDO $pdo, int $employeeId): ?string
    {
        $statement = $pdo->prepare('SELECT status FROM employees WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $employeeId]);
        $status = $statement->fetchColumn();

        return $status === false ? null : (string) $status;
    }

    private function isDuplicate(PDOException $exception): bool
    {
        return (string) ($exception->errorInfo[0] ?? $exception->getCode()) === '23000'
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
