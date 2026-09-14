<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AppointmentIntegrationSettingsService;
use RuntimeException;

final class AppointmentFeeController extends Controller
{
    public function show(Request $req): array
    {
        $rials = max(0, (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0));
        return $this->success([
            'amount_rials' => $rials,
            'amount_tomans' => intdiv($rials, 10),
            'gateway' => 'zarinpal',
        ]);
    }

    public function update(Request $req): array
    {
        $tomans = (int)($req->body['amount_tomans'] ?? 0);
        if ($tomans < 1000 || $tomans > 200000000) {
            return $this->validationError([['field' => 'amount_tomans', 'message' => 'مبلغ ویزیت معتبر نیست']]);
        }
        $rials = $tomans * 10;
        try {
            (new AppointmentIntegrationSettingsService())->update([
                'deposit_rials' => $rials,
                'payment_gateways' => ['zarinpal'],
            ]);
            return $this->success([
                'updated' => true,
                'amount_rials' => $rials,
                'amount_tomans' => $tomans,
                'gateway' => 'zarinpal',
            ]);
        } catch (RuntimeException $e) {
            return $this->error('به‌روزرسانی مبلغ ویزیت انجام نشد', 422);
        }
    }
}
