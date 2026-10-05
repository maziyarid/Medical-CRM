<?php
declare(strict_types=1);

namespace App\Services;

/** Read-only, hard-disabled MS Robot boundary. No transport or credentials. */
final class SeoReportingService
{
    private const PERIODS = ['last_7d', 'last_14d', 'last_28d', 'last_30d', 'last_90d'];

    public static function canView(array $user): bool
    {
        if (($user['user_type'] ?? '') !== 'staff'
            || filter_var($user['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
            || filter_var($user['clinic_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return false;
        }
        $roles = $user['roles'] ?? [$user['role'] ?? ''];
        if (!is_array($roles)) return false;
        foreach ($roles as $role) {
            if ($role === 'super_admin' || $role === 'admin') return true;
        }
        return false;
    }

    public static function validQuery(array $query): bool
    {
        if (array_diff(array_keys($query), ['period', 'comparison', 'endDate']) !== []) return false;
        if (!in_array($query['period'] ?? 'last_28d', self::PERIODS, true)) return false;
        if (!in_array($query['comparison'] ?? 'previous', ['previous', 'none'], true)) return false;
        if (array_key_exists('endDate', $query) && $query['endDate'] !== '') {
            $value = $query['endDate'];
            if (!is_string($value) || !preg_match('/^(?!0000)\d{4}-\d{2}-\d{2}$/D', $value)) return false;
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
            if ($date === false || $date->format('Y-m-d') !== $value || $value > gmdate('Y-m-d')) return false;
        }
        $closing = $query['endDate'] ?? gmdate('Y-m-d');
        if ($closing === '') $closing = gmdate('Y-m-d');
        $days = (int)preg_replace('/\D/', '', $query['period'] ?? 'last_28d');
        $offset = (($query['comparison'] ?? 'previous') === 'previous' ? 2 * $days : $days) - 1;
        $first = (new \DateTimeImmutable($closing, new \DateTimeZone('UTC')))->modify('-' . $offset . ' days')->format('Y-m-d');
        if (!preg_match('/^(?!0000)\d{4}-\d{2}-\d{2}$/D', $first)) return false;
        return true;
    }

    public function read(): array
    {
        // Intentionally no env toggle, URL, provider credential or token exchange.
        // Enablement requires reviewed identity, clinic/project/site mapping and
        // MS Robot launch gates; a domain name is not evidence of membership.
        return [
            'ok' => false,
            'status' => 503,
            'data' => null,
            'errors' => [['field' => null, 'message' => 'اتصال گزارش‌های MS Robot هنوز آماده نیست.']],
            'meta' => ['code' => 'reporting_unconfigured', 'source' => 'ms_robot', 'snapshotSchemaVersion' => 'ms-robot.reporting.v1'],
        ];
    }
}
