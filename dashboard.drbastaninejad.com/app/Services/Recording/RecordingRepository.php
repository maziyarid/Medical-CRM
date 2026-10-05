<?php
declare(strict_types=1);
namespace App\Services\Recording;

/** No production implementation is supplied. All operations are tenant scoped.
 * transaction MUST serialize session, consent, policy/access changes, idempotency
 * reservations and job/draft writes, rolling everything back on any exception.
 * load/consent are fresh, locked reads; save uses optimistic version + durable
 * commit. Unique scope/request and scope/session/sequence keys are mandatory.
 */
interface RecordingRepository
{
    public function transaction(callable $operation): mixed;
    public function findByRequest(array $scope, string $requestKey): ?array;
    public function load(array $scope, string $sessionId): ?array;
    public function save(array $scope, array $session, ?int $expectedVersion): void;
    public function consent(array $scope, string $receiptId): ?array;
    public function revokeConsent(array $scope, string $receiptId, int $at): void;
}
