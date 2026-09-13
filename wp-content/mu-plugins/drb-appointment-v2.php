<?php
/**
 * Plugin Name: DRB Appointment Booking v2
 * Description: Public slot/payment bridge and protected WordPress-admin controls.
 */

defined('ABSPATH') || exit;

function drb_v2_dashboard_base() {
    if (function_exists('drb_dashboard_api_base')) {
        return drb_dashboard_api_base();
    }
    if (defined('DRB_DASHBOARD_API_BASE') && DRB_DASHBOARD_API_BASE) {
        return untrailingslashit(DRB_DASHBOARD_API_BASE);
    }
    $env = getenv('DRB_DASHBOARD_API_BASE');
    return $env ? untrailingslashit($env) : 'https://dashboard.drbastaninejad.com/api/v1';
}

function drb_v2_bridge_secret() {
    if (function_exists('drb_booking_bridge_secret')) {
        return drb_booking_bridge_secret();
    }
    if (defined('DRB_WORDPRESS_BRIDGE_SECRET') && DRB_WORDPRESS_BRIDGE_SECRET) {
        return (string) DRB_WORDPRESS_BRIDGE_SECRET;
    }
    $env = getenv('WORDPRESS_BRIDGE_SECRET');
    return $env ? (string) $env : '';
}

function drb_v2_nonce_valid(WP_REST_Request $request) {
    $nonce = (string) $request->get_header('X-DRB-Form-Nonce');
    if ($nonce && wp_verify_nonce($nonce, 'drb_public_form')) {
        return true;
    }
    $param = (string) $request->get_param('_wpnonce');
    return $param && wp_verify_nonce($param, 'drb_public_form');
}

function drb_v2_sign_intake($intake_id) {
    $intake_id = absint($intake_id);
    $secret = drb_v2_bridge_secret();
    if (!$intake_id || '' === $secret) {
        return '';
    }
    $expires = time() + HOUR_IN_SECONDS;
    $payload = $intake_id . '.' . $expires;
    return $payload . '.' . hash_hmac('sha256', $payload, $secret);
}

function drb_v2_verify_intake_token($token) {
    $parts = explode('.', (string) $token);
    if (3 !== count($parts)) {
        return 0;
    }
    [$id, $expires, $signature] = $parts;
    if (!ctype_digit($id) || !ctype_digit($expires) || (int) $expires < time()) {
        return 0;
    }
    $secret = drb_v2_bridge_secret();
    if ('' === $secret || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
        return 0;
    }
    $payload = $id . '.' . $expires;
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature) ? absint($id) : 0;
}

/**
 * @return array|WP_Error Decoded dashboard envelope data or WP_Error.
 */
function drb_v2_dashboard_request($method, $path, $body = null, $query = array()) {
    $secret = drb_v2_bridge_secret();
    if ('' === $secret) {
        return new WP_Error('drb_v2_unconfigured', 'پل امن داشبورد تنظیم نشده است.', array('status' => 503));
    }

    $url = drb_v2_dashboard_base() . '/' . ltrim($path, '/');
    if ($query) {
        $url = add_query_arg($query, $url);
    }
    $args = array(
        'method' => strtoupper($method),
        'timeout' => 25,
        'redirection' => 0,
        'sslverify' => true,
        'reject_unsafe_urls' => true,
        'httpversion' => '1.1',
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
            'X-WordPress-Bridge-Secret' => $secret,
        ),
    );
    if (null !== $body) {
        $args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return new WP_Error('drb_v2_transport', 'ارتباط با سامانه نوبت‌دهی برقرار نشد.', array(
            'status' => 503,
            'technical' => $response->get_error_message(),
        ));
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['ok'])) {
        $message = is_array($decoded) && !empty($decoded['errors'][0]['message'])
            ? sanitize_text_field((string) $decoded['errors'][0]['message'])
            : 'سامانه نوبت‌دهی درخواست را نپذیرفت.';
        return new WP_Error('drb_v2_remote', $message, array('status' => $status >= 400 ? $status : 502));
    }
    return $decoded['data'] ?? array();
}

add_filter('rest_post_dispatch', function ($response, $server, $request) {
    if (!$request instanceof WP_REST_Request || '/drb/v1/appointment' !== $request->get_route()) {
        return $response;
    }
    if (!is_object($response) || !method_exists($response, 'get_data') || !method_exists($response, 'set_data')) {
        return $response;
    }
    $data = $response->get_data();
    if (!is_array($data) || empty($data['success']) || empty($data['bookingId'])) {
        return $response;
    }
    $token = drb_v2_sign_intake((int) $data['bookingId']);
    if ($token) {
        $data['appointmentToken'] = $token;
        $response->set_data($data);
    }
    return $response;
}, 20, 3);

add_action('rest_api_init', function () {
    register_rest_route('drb/v1', '/appointment-v2/availability', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            if (!drb_v2_nonce_valid($request)) {
                return new WP_Error('invalid_nonce', 'نشست فرم منقضی شده است. صفحه را تازه‌سازی کنید.', array('status' => 403));
            }
            $query = array();
            foreach (array('from', 'to') as $key) {
                $value = sanitize_text_field((string) $request->get_param($key));
                if ($value) {
                    $query[$key] = $value;
                }
            }
            $data = drb_v2_dashboard_request('GET', 'appointment-bookings/availability', null, $query);
            if (is_wp_error($data)) {
                return $data;
            }
            return new WP_REST_Response(array(
                'success' => true,
                'days' => array_values((array) ($data['days'] ?? array())),
                'timezone' => sanitize_text_field((string) ($data['timezone'] ?? 'Asia/Tehran')),
            ));
        },
    ));

    register_rest_route('drb/v1', '/appointment-v2/checkout', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            if (!drb_v2_nonce_valid($request)) {
                return new WP_Error('invalid_nonce', 'نشست فرم منقضی شده است. صفحه را تازه‌سازی کنید.', array('status' => 403));
            }
            $body = (array) $request->get_json_params();
            $intake_id = drb_v2_verify_intake_token($body['appointment_token'] ?? '');
            if (!$intake_id) {
                return new WP_Error('invalid_appointment_token', 'مجوز انتخاب نوبت منقضی شده است. فرم را دوباره ثبت کنید.', array('status' => 403));
            }
            $gateway = strtolower(sanitize_key($body['gateway'] ?? ''));
            if (!in_array($gateway, array('zarinpal', 'vandar'), true)) {
                return new WP_Error('invalid_gateway', 'درگاه پرداخت معتبر نیست.', array('status' => 422));
            }
            $payload = array(
                'intake_id' => $intake_id,
                'open_day_id' => absint($body['open_day_id'] ?? 0),
                'start_at' => sanitize_text_field((string) ($body['start_at'] ?? '')),
                'gateway' => $gateway,
            );
            if (!$payload['open_day_id'] || '' === $payload['start_at']) {
                return new WP_Error('invalid_slot', 'زمان نوبت معتبر نیست.', array('status' => 422));
            }
            $data = drb_v2_dashboard_request('POST', 'bookings/v2/checkout', $payload);
            if (is_wp_error($data)) {
                return $data;
            }
            $redirect = esc_url_raw((string) ($data['payment']['redirect_url'] ?? ''));
            if (!$redirect) {
                return new WP_Error('payment_redirect_missing', 'لینک درگاه پرداخت دریافت نشد.', array('status' => 502));
            }
            return new WP_REST_Response(array(
                'success' => true,
                'redirectUrl' => $redirect,
                'holdMinutes' => absint($data['booking']['hold_minutes'] ?? 0),
                'bookingRequestId' => absint($data['booking']['id'] ?? 0),
            ), 201);
        },
    ));
});

add_action('wp_enqueue_scripts', function () {
    if (is_admin() || !wp_script_is('drb-booking-minimal', 'enqueued')) {
        return;
    }
    $extra = array(
        'availability' => rest_url('drb/v1/appointment-v2/availability'),
        'checkout' => rest_url('drb/v1/appointment-v2/checkout'),
    );
    wp_add_inline_script(
        'drb-booking-minimal',
        'window.__DRB_BOOKING_API__=Object.assign(window.__DRB_BOOKING_API__||{},' . wp_json_encode($extra) . ');',
        'before'
    );
}, 40);

add_action('admin_menu', function () {
    add_management_page(
        'نوبت آنلاین',
        'نوبت آنلاین',
        'manage_options',
        'drb-online-appointments',
        'drb_v2_render_admin_page'
    );
});

function drb_v2_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }

    $notice = '';
    $notice_class = 'notice-success';
    if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['drb_v2_action'])) {
        check_admin_referer('drb_v2_admin_action');
        $action = sanitize_key(wp_unslash($_POST['drb_v2_action']));
        if ('setup' === $action) {
            $result = drb_v2_dashboard_request('POST', 'bookings/v2/setup', array());
            if (is_wp_error($result)) {
                $notice = $result->get_error_message();
                $notice_class = 'notice-error';
            } else {
                $notice = empty($result['errors']) ? 'ساختار دیتابیس نوبت‌دهی بررسی و آماده شد.' : 'به‌روزرسانی انجام شد اما بعضی مراحل نیاز به بررسی دارند.';
                $notice_class = empty($result['errors']) ? 'notice-success' : 'notice-warning';
            }
        } elseif (in_array($action, array('save_day', 'close_day'), true)) {
            $payload = array(
                'open_date' => sanitize_text_field(wp_unslash($_POST['open_date'] ?? '')),
                'opens_at' => sanitize_text_field(wp_unslash($_POST['opens_at'] ?? '')),
                'closes_at' => sanitize_text_field(wp_unslash($_POST['closes_at'] ?? '')),
                'capacity' => max(1, absint($_POST['capacity'] ?? 20)),
                'slot_duration_minutes' => max(5, absint($_POST['slot_duration_minutes'] ?? 20)),
                'status' => 'close_day' === $action ? 'closed' : 'open',
            );
            $result = drb_v2_dashboard_request('POST', 'bookings/v2/open-days', $payload);
            if (is_wp_error($result)) {
                $notice = $result->get_error_message();
                $notice_class = 'notice-error';
            } else {
                $notice = 'تنظیم روز نوبت‌دهی ذخیره شد.';
            }
        }
    }

    $status = drb_v2_dashboard_request('GET', 'bookings/v2/status');
    $days_data = drb_v2_dashboard_request('GET', 'bookings/v2/open-days');
    $days = is_wp_error($days_data) ? array() : (array) ($days_data['days'] ?? array());
    ?>
    <div class="wrap" dir="rtl">
      <h1>نوبت آنلاین</h1>
      <p>مدیریت روزهای باز، ظرفیت و آمادگی سیستم پرداخت. سقف سیستم: حداکثر ۲ روز باز در هر هفته و ۸ روز در هر ماه.</p>
      <?php if ($notice) : ?>
        <div class="notice <?php echo esc_attr($notice_class); ?> is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
      <?php endif; ?>

      <style>
        .drb-v2-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;max-width:1000px}.drb-v2-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.drb-v2-ok{color:#157347;font-weight:700}.drb-v2-bad{color:#b42318;font-weight:700}.drb-v2-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;align-items:end}.drb-v2-form label{display:grid;gap:5px}.drb-v2-table td,.drb-v2-table th{text-align:right}
      </style>

      <h2>آمادگی سیستم</h2>
      <?php if (is_wp_error($status)) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($status->get_error_message()); ?></p></div>
      <?php else : ?>
        <div class="drb-v2-grid">
          <?php
          $checks = array(
              'ساختار دیتابیس' => !empty($status['schema_ready']),
              'مبلغ بیعانه' => !empty($status['deposit_configured']),
              'Callback پرداخت' => !empty($status['callback_configured']),
              'زرین‌پال' => !empty($status['gateways']['zarinpal']),
              'وندار' => !empty($status['gateways']['vandar']),
              'Google Calendar' => !empty($status['calendar_configured']),
          );
          foreach ($checks as $label => $ok) : ?>
            <div class="drb-v2-card"><strong><?php echo esc_html($label); ?></strong><p class="<?php echo $ok ? 'drb-v2-ok' : 'drb-v2-bad'; ?>"><?php echo $ok ? 'آماده' : 'نیاز به تنظیم'; ?></p></div>
          <?php endforeach; ?>
        </div>
        <p>روزهای باز آینده: <strong><?php echo esc_html((string) absint($status['open_days_next_60'] ?? 0)); ?></strong>
        <?php if (!empty($status['deposit_rials'])) : ?> — بیعانه: <strong><?php echo esc_html(number_format_i18n((int) $status['deposit_rials'])); ?> ریال</strong><?php endif; ?></p>
      <?php endif; ?>

      <form method="post" style="margin:16px 0 28px">
        <?php wp_nonce_field('drb_v2_admin_action'); ?>
        <input type="hidden" name="drb_v2_action" value="setup">
        <?php submit_button('بررسی و اعمال به‌روزرسانی دیتابیس', 'secondary', 'submit', false); ?>
      </form>

      <h2>افزودن یا ویرایش روز باز</h2>
      <div class="drb-v2-card" style="max-width:1000px">
        <form method="post" class="drb-v2-form">
          <?php wp_nonce_field('drb_v2_admin_action'); ?>
          <input type="hidden" name="drb_v2_action" value="save_day">
          <label>تاریخ <input type="date" name="open_date" required></label>
          <label>شروع <input type="time" name="opens_at" required></label>
          <label>پایان <input type="time" name="closes_at" required></label>
          <label>ظرفیت روز <input type="number" name="capacity" min="1" max="200" value="20" required></label>
          <label>مدت هر نوبت (دقیقه) <input type="number" name="slot_duration_minutes" min="5" max="240" value="20" required></label>
          <div><?php submit_button('ذخیره روز', 'primary', 'submit', false); ?></div>
        </form>
      </div>

      <h2>روزهای تنظیم‌شده</h2>
      <?php if (!$days) : ?>
        <p>هنوز روز بازی برای ۶۰ روز آینده تنظیم نشده است.</p>
      <?php else : ?>
        <table class="widefat striped drb-v2-table">
          <thead><tr><th>تاریخ</th><th>ساعت</th><th>ظرفیت</th><th>رزرو/نگهداری</th><th>زمان‌های آزاد</th><th>عملیات</th></tr></thead>
          <tbody>
          <?php foreach ($days as $day) : ?>
            <tr>
              <td><?php echo esc_html((string) ($day['date'] ?? $day['open_date'] ?? '')); ?></td>
              <td><?php echo esc_html(substr((string) ($day['opens_at'] ?? ''), 0, 5) . ' – ' . substr((string) ($day['closes_at'] ?? ''), 0, 5)); ?></td>
              <td><?php echo esc_html((string) absint($day['capacity'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) absint($day['booked_count'] ?? 0)); ?> / <?php echo esc_html((string) absint($day['held_count'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) count((array) ($day['slots'] ?? array()))); ?></td>
              <td>
                <?php if ('open' === ($day['status'] ?? 'open')) : ?>
                  <form method="post" style="display:inline">
                    <?php wp_nonce_field('drb_v2_admin_action'); ?>
                    <input type="hidden" name="drb_v2_action" value="close_day">
                    <input type="hidden" name="open_date" value="<?php echo esc_attr((string) ($day['date'] ?? $day['open_date'] ?? '')); ?>">
                    <input type="hidden" name="opens_at" value="<?php echo esc_attr(substr((string) ($day['opens_at'] ?? ''), 0, 5)); ?>">
                    <input type="hidden" name="closes_at" value="<?php echo esc_attr(substr((string) ($day['closes_at'] ?? ''), 0, 5)); ?>">
                    <input type="hidden" name="capacity" value="<?php echo esc_attr((string) absint($day['capacity'] ?? 20)); ?>">
                    <input type="hidden" name="slot_duration_minutes" value="<?php echo esc_attr((string) absint($day['slot_duration_minutes'] ?? 20)); ?>">
                    <button class="button" type="submit">بستن روز</button>
                  </form>
                <?php else : ?> بسته <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php
}
