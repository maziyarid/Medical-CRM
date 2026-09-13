<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AppointmentBookingService;
use App\Services\CalendarSyncService;

final class WordPressCalendarBridgeController extends Controller
{
    public function sync(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل تقویم معتبر نیست.', 401);
        }

        foreach (['GOOGLE_CALENDAR_ID', 'GOOGLE_CALENDAR_CLIENT_ID', 'GOOGLE_CALENDAR_CLIENT_SECRET', 'GOOGLE_CALENDAR_REFRESH_TOKEN'] as $key) {
            if (trim((string)($_ENV[$key] ?? '')) === '') {
                return $this->success([
                    'skipped' => true,
                    'reason' => 'calendar_not_configured',
                ]);
            }
        }

        try {
            $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
            $released = (new AppointmentBookingService())->releaseExpiredHolds($clinicId);
            $sync = new CalendarSyncService();
            $pushed = $sync->pushOutbox(max(1, min(100, (int)($_ENV['CALENDAR_SYNC_BATCH_SIZE'] ?? 25))));
            $pulled = $sync->pullChanges($clinicId);
            return $this->success([
                'skipped' => false,
                'expired_holds_released' => $released,
                'push' => $pushed,
                'pull' => $pulled,
            ]);
        } catch (\Throwable $e) {
            error_log('[WordPressCalendarBridgeController] sync failed: ' . $e->getMessage());
            return $this->error('همگام‌سازی تقویم انجام نشد.', 503);
        }
    }

    private function authorised(Request $req): bool
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }
}
