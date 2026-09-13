<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * Clinic-level settings and read-only operational status.
 * Secrets never leave the server; integrations expose configuration state only.
 */
final class SettingsController extends Controller
{
    public function show(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        $stmt = $db->prepare(
            'SELECT id, name, phone, address, timezone, working_hours_json, created_at, updated_at
             FROM clinics WHERE id = ?'
        );
        $stmt->execute([$clinicId]);
        $row = $stmt->fetch();
        if (!$row) {
            return $this->error('تنظیمات کلینیک یافت نشد', 404);
        }

        $workingHours = [];
        if (!empty($row['working_hours_json'])) {
            $decoded = json_decode((string)$row['working_hours_json'], true);
            if (is_array($decoded)) {
                $workingHours = $decoded;
            }
        }

        $tplStmt = $db->prepare(
            'SELECT id, name, specialty, schema_json, created_at
             FROM emr_templates
             WHERE clinic_id IS NULL OR clinic_id = ?
             ORDER BY specialty ASC, name ASC'
        );
        $tplStmt->execute([$clinicId]);
        $emrTemplates = $tplStmt->fetchAll();

        $userStmt = $db->prepare(
            'SELECT u.id, u.uuid, u.full_name, u.is_active,
                    GROUP_CONCAT(DISTINCT COALESCE(r.label, r.name) ORDER BY r.name SEPARATOR "، ") AS roles
             FROM users u
             LEFT JOIN role_user ru ON ru.user_id = u.id
             LEFT JOIN roles r ON r.id = ru.role_id
             WHERE u.clinic_id = ? AND u.deleted_at IS NULL
             GROUP BY u.id, u.uuid, u.full_name, u.is_active
             ORDER BY u.full_name ASC'
        );
        $userStmt->execute([$clinicId]);
        $users = array_map(static fn(array $u): array => [
            'id' => (int)$u['id'],
            'uuid' => (string)$u['uuid'],
            'full_name' => (string)$u['full_name'],
            'roles' => $u['roles'] ?: 'بدون نقش',
            'is_active' => (bool)$u['is_active'],
        ], $userStmt->fetchAll());

        return $this->success([
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'timezone' => $row['timezone'] ?? 'Asia/Tehran',
            'working_hours' => $workingHours,
            'updated_at' => $row['updated_at'],
            'emr_templates' => $emrTemplates,
            'users' => $users,
            'integrations' => $this->integrationStatus(),
        ]);
    }

    /**
     * PATCH /api/v1/settings/clinic
     * Body (all optional): { name, phone, address, timezone, working_hours }
     */
    public function update(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;
        $db = Database::conn();

        $check = $db->prepare('SELECT id FROM clinics WHERE id = ?');
        $check->execute([$clinicId]);
        if (!$check->fetch()) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        $sets = [];
        $params = [];
        foreach (['name', 'phone', 'address', 'timezone'] as $field) {
            if (!array_key_exists($field, $in)) {
                continue;
            }
            $value = trim((string)$in[$field]);
            if ($field === 'name' && ($value === '' || mb_strlen($value) > 255)) {
                return $this->validationError([['field' => 'name', 'message' => 'نام کلینیک معتبر نیست']]);
            }
            if ($field === 'phone' && mb_strlen($value) > 32) {
                return $this->validationError([['field' => 'phone', 'message' => 'شماره تماس بیش از حد طولانی است']]);
            }
            if ($field === 'timezone' && $value !== 'Asia/Tehran') {
                return $this->validationError([['field' => 'timezone', 'message' => 'منطقه زمانی پشتیبانی نمی‌شود']]);
            }
            $sets[] = "{$field} = ?";
            $params[] = $value !== '' ? $value : null;
        }

        if (array_key_exists('working_hours', $in)) {
            try {
                $hours = $this->normaliseWorkingHours($in['working_hours']);
            } catch (\InvalidArgumentException $e) {
                return $this->validationError([['field' => 'working_hours', 'message' => $e->getMessage()]]);
            }
            $sets[] = 'working_hours_json = ?';
            $params[] = json_encode($hours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!$sets) {
            return $this->error('هیچ فیلدی برای به‌روزرسانی ارسال نشده', 422);
        }

        $sets[] = 'updated_at = UTC_TIMESTAMP()';
        $params[] = $clinicId;
        $db->prepare('UPDATE clinics SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

        return $this->success(['id' => $clinicId, 'updated' => true]);
    }

    /** @return array<int,array{day:string,active:bool,open:?string,close:?string}> */
    private function normaliseWorkingHours(mixed $value): array
    {
        if (!is_array($value) || count($value) > 7) {
            throw new \InvalidArgumentException('ساعت کاری باید حداکثر شامل هفت روز باشد.');
        }
        $allowedDays = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];
        $seen = [];
        $out = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('ساختار ساعت کاری معتبر نیست.');
            }
            $day = strtolower(trim((string)($item['day'] ?? '')));
            if (!in_array($day, $allowedDays, true) || isset($seen[$day])) {
                throw new \InvalidArgumentException('روز ساعت کاری معتبر یا یکتا نیست.');
            }
            $seen[$day] = true;
            $active = filter_var($item['active'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $active = $active ?? false;
            $open = trim((string)($item['open'] ?? ''));
            $close = trim((string)($item['close'] ?? ''));
            if ($active) {
                if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $open) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $close) || $open >= $close) {
                    throw new \InvalidArgumentException('برای روز فعال، ساعت شروع و پایان معتبر وارد کنید.');
                }
            } else {
                $open = $open !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $open) ? $open : null;
                $close = $close !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $close) ? $close : null;
            }
            $out[] = ['day' => $day, 'active' => $active, 'open' => $open ?: null, 'close' => $close ?: null];
        }
        return $out;
    }

    /** @return array<int,array{key:string,label:string,configured:bool,detail:string}> */
    private function integrationStatus(): array
    {
        $has = static fn(string $key): bool => trim((string)($_ENV[$key] ?? '')) !== ''
            && !str_starts_with(trim((string)($_ENV[$key] ?? '')), 'CHANGE_ME');
        $smsConfigured = $has('KAVENEGAR_API_KEY') || $has('GHASEDAK_API_KEY')
            || ($has('FARAZSMS_USERNAME') && $has('FARAZSMS_PASSWORD'))
            || ($has('TSMS_USERNAME') && $has('TSMS_PASSWORD') && $has('TSMS_FROM'));
        $youKey = $has('YOU_API_KEY') || $has('YDC_API_KEY');
        $orKey = $has('OPENROUTER_API_KEY');
        $aiOn = (($_ENV['AI_ENABLED'] ?? '0') === '1') && ($youKey || $orKey);
        $emailRecovery = (($_ENV['EMAIL_OTP_ENABLED'] ?? '0') === '1') && $has('MAIL_FROM');
        $bookingSheet = (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') === '1')
            && $has('BOOKING_SHEET_WEBHOOK_URL') && $has('BOOKING_SHEET_SHARED_SECRET');
        $bookingEmail = (($_ENV['BOOKING_EMAIL_ENABLED'] ?? '0') === '1') && $has('MAIL_FROM');
        $provider = strtolower(trim((string)($_ENV['AI_PROVIDER'] ?? 'auto')));

        return [
            ['key' => 'sms', 'label' => 'پیامک', 'configured' => $smsConfigured, 'detail' => 'زنجیره provider از محیط سرور'],
            ['key' => 'email_recovery', 'label' => 'بازیابی ایمیلی', 'configured' => $emailRecovery, 'detail' => 'OTP ایمیل؛ رمز عبور ارسال نمی‌شود'],
            ['key' => 'you_com', 'label' => 'You.com API', 'configured' => $youKey && $aiOn, 'detail' => $youKey ? ('provider=' . $provider) : 'YOU_API_KEY missing'],
            ['key' => 'openrouter', 'label' => 'OpenRouter / AI', 'configured' => $orKey && $aiOn, 'detail' => (string)($_ENV['OPENROUTER_MODEL'] ?? 'مدل تنظیم نشده')],
            ['key' => 'intake_bridge', 'label' => 'پل پذیرش WorkingVersion', 'configured' => $has('INTAKE_BRIDGE_SECRET'), 'detail' => 'secret فقط سمت سرور'],
            ['key' => 'wordpress_bridge', 'label' => 'پل رزرو WordPress', 'configured' => $has('WORDPRESS_BRIDGE_SECRET'), 'detail' => 'secret فقط سمت سرور'],
            ['key' => 'booking_sheet', 'label' => 'شیت درخواست نوبت', 'configured' => $bookingSheet, 'detail' => 'وب‌هوک جداگانه Booking؛ secret فقط سمت سرور'],
            ['key' => 'booking_email', 'label' => 'ایمیل ثبت درخواست نوبت', 'configured' => $bookingEmail, 'detail' => 'فقط در صورت تکمیل ایمیل بیمار'],
        ];
    }
}
