-- Migration 026 — explicit Google Calendar conflict resolution state.

ALTER TABLE `calendar_sync_state`
    ADD COLUMN IF NOT EXISTS `remote_starts_at` DATETIME NULL AFTER `last_error`,
    ADD COLUMN IF NOT EXISTS `remote_ends_at` DATETIME NULL AFTER `remote_starts_at`;
