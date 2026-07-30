<?php
declare(strict_types=1);

namespace App\Services;

/**
 * OtpService — RETIRED (dashboard.drbastaninejad.com)
 *
 * RECONCILIATION DECISION (2026-07-31, Blackbox AI — see PROGRESS_LOG.md):
 *
 *   The canonical OtpService for the entire platform is:
 *     app.drbastaninejad.com/Backend/app/Services/OtpService.php
 *
 *   That file is a superset of this one — identical SQL, bcrypt storage, token
 *   issuance — PLUS a dev-mode OTP log for non-production environments.
 *   Both share the same namespace (App\Services) and the same class name.
 *
 *   This stub file is kept so that any dashboard code that already imports
 *   App\Services\OtpService can resolve the class without a fatal error,
 *   but it re-exports the canonical by requiring it from the shared location.
 *   When the two backends are merged (Phase D), delete this file entirely.
 *
 *   RULE: Never make changes here. All OTP logic lives in the canonical file.
 */

// Forward to the canonical implementation via an absolute path constant
// set in the dashboard bootstrap / public/index.php.
if (!class_exists(OtpService::class, false)) {
    $canonicalPath = defined('APP_ROOT')
        ? APP_ROOT . '/../app.drbastaninejad.com/Backend/app/Services/OtpService.php'
        : __DIR__ . '/../../../../app.drbastaninejad.com/Backend/app/Services/OtpService.php';

    if (is_file($canonicalPath)) {
        require_once $canonicalPath;
    } else {
        // During local development the paths may differ — throw a clear error
        // rather than silently running with a missing service.
        throw new \LogicException(
            '[OtpService stub] Cannot locate canonical implementation at: ' . $canonicalPath .
            '. Set APP_ROOT in your bootstrap or use a symlink.'
        );
    }
}
