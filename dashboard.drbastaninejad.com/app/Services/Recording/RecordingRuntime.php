<?php
declare(strict_types=1);
namespace App\Services\Recording;
interface RecordingRuntime
{
    /** Server-authoritative epoch milliseconds. Never supplied by an HTTP caller. */
    public function nowMs(): int;
    /** Cryptographically unpredictable opaque server ID, 1..128 ASCII characters. */
    public function newId(): string;
    /** Explicitly approved persisted policy version, or null when unresolved. */
    public function consentPolicyVersion(): ?string;
    /** Approved configured provider name/version and pinned model/revision, or null. No ASR implementation. */
    public function provider(): ?array;
}
