<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\EmployeeProjectInput;
use App\Database\LazyPdoConnection;
use App\Domain\Employee\EmployeeProjectRepositoryInterface;
use App\Domain\Employee\PortfolioWriteResult;
use PDO;
use Throwable;

final class PdoEmployeeProjectRepository implements EmployeeProjectRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listForEmployee(int $employeeId, bool $includeArchived = true): array
    {
        $sql = 'SELECT id, employee_id, project_name, role, start_date, end_date, description, '
            . 'responsibilities, technologies, status, archived_at, created_at, updated_at '
            . 'FROM employee_projects WHERE employee_id = :employee_id';
        if (!$includeArchived) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY status ASC, start_date DESC, id DESC';
        $statement = $this->connection->get()->prepare($sql);
        $statement->execute(['employee_id' => $employeeId]);
        return $statement->fetchAll();
    }

    public function findByIdForEmployee(int $employeeId, int $projectId): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, employee_id, project_name, role, start_date, end_date, description, '
            . 'responsibilities, technologies, status, archived_at, created_at, updated_at '
            . 'FROM employee_projects WHERE id = :id AND employee_id = :employee_id',
        );
        $statement->execute(['id' => $projectId, 'employee_id' => $employeeId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function insert(int $employeeId, EmployeeProjectInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();
        try {
            $status = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($status === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($status !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }
            $statement = $pdo->prepare(
                "INSERT INTO employee_projects (employee_id, project_name, role, start_date, end_date, description, responsibilities, technologies, status, archived_at, created_at, updated_at)
                 VALUES (:employee_id, :project_name, :role, :start_date, :end_date, :description, :responsibilities, :technologies, 'active', NULL, :created_at, :updated_at)",
            );
            $statement->execute([
                'employee_id' => $employeeId,
                'project_name' => $input->projectName,
                'role' => $input->role,
                'start_date' => $input->startDate,
                'end_date' => $input->endDate,
                'description' => $input->description,
                'responsibilities' => $input->responsibilities,
                'technologies' => $input->technologies,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
            return new PortfolioWriteResult('created', $id);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function update(int $employeeId, int $projectId, EmployeeProjectInput $input, string $updatedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();
        try {
            $status = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($status === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($status !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }
            $current = $this->lockedProject($pdo, $employeeId, $projectId);
            if ($current === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $current['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('archived', $projectId);
            }
            $statement = $pdo->prepare(
                'UPDATE employee_projects SET project_name = :project_name, role = :role, start_date = :start_date, '
                . 'end_date = :end_date, description = :description, responsibilities = :responsibilities, '
                . 'technologies = :technologies, updated_at = :updated_at '
                . 'WHERE id = :id AND employee_id = :employee_id AND status = \'active\'',
            );
            $statement->execute([
                'id' => $projectId,
                'employee_id' => $employeeId,
                'project_name' => $input->projectName,
                'role' => $input->role,
                'start_date' => $input->startDate,
                'end_date' => $input->endDate,
                'description' => $input->description,
                'responsibilities' => $input->responsibilities,
                'technologies' => $input->technologies,
                'updated_at' => $updatedAt,
            ]);
            $pdo->commit();
            return new PortfolioWriteResult('updated', $projectId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function archive(int $employeeId, int $projectId, string $archivedAt): PortfolioWriteResult
    {
        return $this->archiveById($employeeId, $projectId, $archivedAt);
    }

    public function countActiveForEmployee(int $employeeId): int
    {
        $statement = $this->connection->get()->prepare(
            "SELECT COUNT(*) FROM employee_projects WHERE employee_id = :employee_id AND status = 'active'",
        );
        $statement->execute(['employee_id' => $employeeId]);
        return (int) $statement->fetchColumn();
    }

    private function archiveById(int $employeeId, int $projectId, string $archivedAt): PortfolioWriteResult
    {
        $pdo = $this->connection->get();
        $pdo->beginTransaction();
        try {
            $status = $this->lockedEmployeeStatus($pdo, $employeeId);
            if ($status === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ($status !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('inactive');
            }
            $current = $this->lockedProject($pdo, $employeeId, $projectId);
            if ($current === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $current['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('already-archived', $projectId);
            }
            $statement = $pdo->prepare(
                "UPDATE employee_projects SET status = 'archived', archived_at = :archived_at, updated_at = :updated_at
                 WHERE id = :id AND employee_id = :employee_id AND status = 'active'",
            );
            $statement->execute(['id' => $projectId, 'employee_id' => $employeeId, 'archived_at' => $archivedAt, 'updated_at' => $archivedAt]);
            $pdo->commit();
            return new PortfolioWriteResult('archived', $projectId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    private function lockedEmployeeStatus(PDO $pdo, int $employeeId): ?string
    {
        $statement = $pdo->prepare('SELECT status FROM employees WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $employeeId]);
        $status = $statement->fetchColumn();
        return $status === false ? null : (string) $status;
    }

    /** @return array<string,mixed>|null */
    private function lockedProject(PDO $pdo, int $employeeId, int $projectId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, status FROM employee_projects WHERE id = :id AND employee_id = :employee_id FOR UPDATE',
        );
        $statement->execute(['id' => $projectId, 'employee_id' => $employeeId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }
}

