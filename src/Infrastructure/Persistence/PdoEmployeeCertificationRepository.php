<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\DTO\EmployeeCertificationInput;
use App\Database\LazyPdoConnection;
use App\Domain\Employee\EmployeeCertificationRepositoryInterface;
use App\Domain\Employee\PortfolioDuplicateException;
use App\Domain\Employee\PortfolioWriteResult;
use PDO;
use PDOException;
use Throwable;

final class PdoEmployeeCertificationRepository implements EmployeeCertificationRepositoryInterface
{
    public function __construct(private readonly LazyPdoConnection $connection)
    {
    }

    public function listForEmployee(int $employeeId, bool $includeArchived = true): array
    {
        $sql = 'SELECT id, employee_id, certification_name, issuing_organization, obtained_date, '
            . 'expiration_date, credential_identifier, notes, status, archived_at, created_at, updated_at '
            . 'FROM employee_certifications WHERE employee_id = :employee_id';
        if (!$includeArchived) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY status ASC, obtained_date DESC, id DESC';
        $statement = $this->connection->get()->prepare($sql);
        $statement->execute(['employee_id' => $employeeId]);
        return $statement->fetchAll();
    }

    public function findByIdForEmployee(int $employeeId, int $certificationId): ?array
    {
        $statement = $this->connection->get()->prepare(
            'SELECT id, employee_id, certification_name, issuing_organization, obtained_date, '
            . 'expiration_date, credential_identifier, notes, status, archived_at, created_at, updated_at '
            . 'FROM employee_certifications WHERE id = :id AND employee_id = :employee_id',
        );
        $statement->execute(['id' => $certificationId, 'employee_id' => $employeeId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function insert(int $employeeId, EmployeeCertificationInput $input, string $createdAt, string $updatedAt): PortfolioWriteResult
    {
        return $this->persist($employeeId, null, $input, $createdAt, $updatedAt);
    }

    public function update(int $employeeId, int $certificationId, EmployeeCertificationInput $input, string $updatedAt): PortfolioWriteResult
    {
        return $this->persist($employeeId, $certificationId, $input, null, $updatedAt);
    }

    public function archive(int $employeeId, int $certificationId, string $archivedAt): PortfolioWriteResult
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
            $current = $this->lockedCertification($pdo, $employeeId, $certificationId);
            if ($current === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $current['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('already-archived', $certificationId);
            }
            $statement = $pdo->prepare(
                "UPDATE employee_certifications SET status = 'archived', archived_at = :archived_at, updated_at = :updated_at
                 WHERE id = :id AND employee_id = :employee_id AND status = 'active'",
            );
            $statement->execute(['id' => $certificationId, 'employee_id' => $employeeId, 'archived_at' => $archivedAt, 'updated_at' => $archivedAt]);
            $pdo->commit();
            return new PortfolioWriteResult('archived', $certificationId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function countActiveForEmployee(int $employeeId): int
    {
        $statement = $this->connection->get()->prepare(
            "SELECT COUNT(*) FROM employee_certifications WHERE employee_id = :employee_id AND status = 'active'",
        );
        $statement->execute(['employee_id' => $employeeId]);
        return (int) $statement->fetchColumn();
    }

    private function persist(int $employeeId, ?int $certificationId, EmployeeCertificationInput $input, ?string $createdAt, string $updatedAt): PortfolioWriteResult
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

            if ($certificationId === null) {
                $statement = $pdo->prepare(
                    "INSERT INTO employee_certifications
                     (employee_id, certification_name, issuing_organization, obtained_date, expiration_date, credential_identifier, notes, status, archived_at, created_at, updated_at)
                     VALUES (:employee_id, :certification_name, :issuing_organization, :obtained_date, :expiration_date, :credential_identifier, :notes, 'active', NULL, :created_at, :updated_at)",
                );
                try {
                    $statement->execute([
                        'employee_id' => $employeeId,
                        'certification_name' => $input->certificationName,
                        'issuing_organization' => $input->issuingOrganization,
                        'obtained_date' => $input->obtainedDate,
                        'expiration_date' => $input->expirationDate,
                        'credential_identifier' => $input->credentialIdentifier,
                        'notes' => $input->notes,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);
                } catch (PDOException $exception) {
                    if ($this->isDuplicate($exception)) {
                        $existing = $this->findIdentityForUpdate($pdo, $employeeId, $input);
                        if ($existing !== null && (string) $existing['status'] === 'archived') {
                            $restore = $pdo->prepare(
                                "UPDATE employee_certifications SET expiration_date = :expiration_date, credential_identifier = :credential_identifier, notes = :notes, status = 'active', archived_at = NULL, updated_at = :updated_at WHERE id = :id AND employee_id = :employee_id",
                            );
                            $restore->execute([
                                'id' => (int) $existing['id'],
                                'employee_id' => $employeeId,
                                'expiration_date' => $input->expirationDate,
                                'credential_identifier' => $input->credentialIdentifier,
                                'notes' => $input->notes,
                                'updated_at' => $updatedAt,
                            ]);
                            $pdo->commit();
                            return new PortfolioWriteResult('restored', (int) $existing['id']);
                        }
                        $pdo->rollBack();
                        return new PortfolioWriteResult('duplicate', $existing === null ? null : (int) $existing['id']);
                    }
                    throw $exception;
                }
                $id = (int) $pdo->lastInsertId();
                $pdo->commit();
                return new PortfolioWriteResult('created', $id);
            }

            $current = $this->lockedCertification($pdo, $employeeId, $certificationId);
            if ($current === null) {
                $pdo->rollBack();
                return new PortfolioWriteResult('missing');
            }
            if ((string) $current['status'] !== 'active') {
                $pdo->rollBack();
                return new PortfolioWriteResult('archived', $certificationId);
            }

            $statement = $pdo->prepare(
                'UPDATE employee_certifications SET certification_name = :certification_name, issuing_organization = :issuing_organization, obtained_date = :obtained_date, expiration_date = :expiration_date, credential_identifier = :credential_identifier, notes = :notes, updated_at = :updated_at WHERE id = :id AND employee_id = :employee_id AND status = \'active\'',
            );
            try {
                $statement->execute([
                    'id' => $certificationId,
                    'employee_id' => $employeeId,
                    'certification_name' => $input->certificationName,
                    'issuing_organization' => $input->issuingOrganization,
                    'obtained_date' => $input->obtainedDate,
                    'expiration_date' => $input->expirationDate,
                    'credential_identifier' => $input->credentialIdentifier,
                    'notes' => $input->notes,
                    'updated_at' => $updatedAt,
                ]);
            } catch (PDOException $exception) {
                if ($this->isDuplicate($exception)) {
                    $pdo->rollBack();
                    return new PortfolioWriteResult('duplicate', $certificationId);
                }
                throw $exception;
            }
            $pdo->commit();
            return new PortfolioWriteResult('updated', $certificationId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array<string,mixed>|null */
    private function findIdentityForUpdate(PDO $pdo, int $employeeId, EmployeeCertificationInput $input): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, status FROM employee_certifications WHERE employee_id = :employee_id AND certification_name = :certification_name AND issuing_organization = :issuing_organization AND obtained_date = :obtained_date FOR UPDATE',
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'certification_name' => $input->certificationName,
            'issuing_organization' => $input->issuingOrganization,
            'obtained_date' => $input->obtainedDate,
        ]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string,mixed>|null */
    private function lockedCertification(PDO $pdo, int $employeeId, int $certificationId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, status FROM employee_certifications WHERE id = :id AND employee_id = :employee_id FOR UPDATE',
        );
        $statement->execute(['id' => $certificationId, 'employee_id' => $employeeId]);
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

