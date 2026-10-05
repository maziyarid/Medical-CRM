<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

/** Apply before auth so denials also cannot enter shared caches. */
final class SeoNoStoreMiddleware
{
    public function handle(Request $req): ?array
    {
        if (!headers_sent()) header('Cache-Control: private, no-store');
        return null;
    }
}
