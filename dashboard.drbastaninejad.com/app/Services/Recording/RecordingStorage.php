<?php
declare(strict_types=1);
namespace App\Services\Recording;
interface RecordingStorage
{
    public function ready(array $scope): bool;
    /** Trusted immutable durable-storage/checksum attestation loaded by scope and
     * sequence, not accepted from HTTP. Return complete chunk identity plus
     * privateObjectId, durable=true, checksumVerified=true. No raw bytes here.
     */
    public function verifiedChunk(array $scope, string $sessionId, int $sequence): ?array;
    /** Trusted remux/decoder result; not client metadata or an ASR claim.
     * Exact immutable manifestHash + audioDurationMs, codecValidated=true,
     * containerValidated=true are required before ASR/draft admission.
     */
    public function verifiedMedia(array $scope, string $sessionId, string $manifestHash): ?array;
}
