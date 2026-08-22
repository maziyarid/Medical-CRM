<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class HealthController extends Controller
{
    public function show(Request $req): array
    {
        unset($req);
        return $this->success([
            'status' => 'ok',
            'service' => 'dashboard-api',
        ]);
    }
}
