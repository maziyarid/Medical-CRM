<?php
declare(strict_types=1);
namespace App\Services\Recording;
interface RecordingAuthority
{
    /** Trusted current patient-clinic/staff permission check. Never a browser grant.
     * Return actorId, patientId, clinicId, expiresAt or null. Missing RBAC denies.
     * Must participate in repository's serialization/revocation boundary.
     */
    public function grant(array $scope, string $permission, int $at): ?array;
}
