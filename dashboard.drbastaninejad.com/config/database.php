<?php
declare(strict_types=1);

/**
 * Database configuration — MΛZ Medical CRM
 *
 * Loaded by App\Core\Database::conn() via:
 *   require BASE_PATH . '/config/database.php';
 *
 * Never commit real credentials. All values come from environment variables
 * (set in cPanel → PHP Environment Variables, or a .env loader at the
 * bootstrap entry point before this file is required).
 */

return [
    // ── Connection ──────────────────────────────────────────────────────────
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => (int)($_ENV['DB_PORT'] ?? 3306),
    'name'    => $_ENV['DB_NAME'] ?? 'medical_crm',
    'user'    => $_ENV['DB_USER'] ?? 'crm_user',
    'pass'    => $_ENV['DB_PASS'] ?? '',

    // ── Application context ─────────────────────────────────────────────────
    // Injected into every clinic-scoped query as the default clinic row.
    // Override per-request after multi-tenant onboarding (v1.5).
    'default_clinic_id' => (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1),

    // ── Environment ─────────────────────────────────────────────────────────
    // 'production' | 'development' | 'staging'
    // In development mode: raw OTPs are logged to error_log (OtpService).
    'app_env' => $_ENV['APP_ENV'] ?? 'production',
];
