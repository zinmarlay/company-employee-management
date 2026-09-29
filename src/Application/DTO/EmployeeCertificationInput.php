<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EmployeeCertificationInput
{
    public function __construct(
        public string $certificationName,
        public string $issuingOrganization,
        public string $obtainedDate,
        public ?string $expirationDate,
        public ?string $credentialIdentifier,
        public ?string $notes,
    ) {
    }
}

