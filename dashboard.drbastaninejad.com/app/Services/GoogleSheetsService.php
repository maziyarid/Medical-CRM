<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GoogleSheetsService — RETIRED (dashboard.drbastaninejad.com)
 *
 * RECONCILIATION DECISION (2026-07-31, Blackbox AI — see PROGRESS_LOG.md):
 *
 *   The canonical GoogleSheetsService for the entire platform is:
 *     app.drbastaninejad.com/Backend/app/Services/GoogleSheetsService.php
 *
 *   REASON this copy was retired:
 *   - The dashboard copy (this file) used a narrow 11-column schema
 *     (A–K: intake_id, submission_uuid, name, mobile, national_id, birth_date,
 *     service_type, chief_complaint, preferred_date, submitted_at, db_id) and
 *     threw RuntimeException on failure — violating the never-throws contract.
 *   - The canonical copy uses the frozen SmartFormat 23-column schema
 *     (A–W) matching UNIFIED_MASTER_PLAN.md §4, returns 'ok'|'skipped'|'failed'
 *     strings instead of throwing, caches OAuth tokens in APCu under a
 *     namespaced key ('gsheets_token_app'), and includes intake_db_id as a
 *     separate cross-reference column.
 *   - The canonical is strictly superior on every dimension.
 *
 *   RULE: Never make changes here. All Sheets logic lives in the canonical file.
 *   When Phase D merges the two backends, delete this file entirely.
 */

if (!class_exists(GoogleSheetsService::class, false)) {
    $canonicalPath = defined('APP_ROOT')
        ? APP_ROOT . '/../app.drbastaninejad.com/Backend/app/Services/GoogleSheetsService.php'
        : __DIR__ . '/../../../../app.drbastaninejad.com/Backend/app/Services/GoogleSheetsService.php';

    if (is_file($canonicalPath)) {
        require_once $canonicalPath;
    } else {
        throw new \LogicException(
            '[GoogleSheetsService stub] Cannot locate canonical implementation at: ' . $canonicalPath .
            '. Set APP_ROOT in your bootstrap or use a symlink.'
        );
    }
}
