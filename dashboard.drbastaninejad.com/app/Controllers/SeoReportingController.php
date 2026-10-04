<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\SeoReportingService;

/** GET /api/v1/analytics/seo; existing auth + analytics.view middleware apply. */
final class SeoReportingController extends Controller
{
    public function summary(Request $req): array
    {
        // This sensitive read must never enter shared browser/CDN caches.
        if (!headers_sent()) header('Cache-Control: private, no-store');
        if ($req->user === null) return $this->error('احراز هویت الزامی است', 401);
        if (!SeoReportingService::canView($req->user)) return $this->error('دسترسی کافی ندارید', 403);
        if (!SeoReportingService::validQuery($req->query)) return $this->validationError([
            ['field' => null, 'message' => 'بازه گزارش معتبر نیست.'],
        ]);
        return (new SeoReportingService())->read();
    }
}
