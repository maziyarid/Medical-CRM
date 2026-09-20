<?php
defined( 'ABSPATH' ) || exit;

function drb_register_form_routes() {
    foreach ( array( 'contact', 'appointment' ) as $kind ) {
        register_rest_route( 'drb/v1', '/' . $kind, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => function( WP_REST_Request $request ) use ( $kind ) { return drb_process_submission( $request, $kind ); },
            'permission_callback' => '__return_true',
        ) );
    }
    register_rest_route( 'drb/v1', '/booking-otp/send', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function( WP_REST_Request $request ) { return drb_proxy_booking_otp( $request, 'send' ); },
        'permission_callback' => '__return_true',
    ) );
    register_rest_route( 'drb/v1', '/booking-otp/verify', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function( WP_REST_Request $request ) { return drb_proxy_booking_otp( $request, 'verify' ); },
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'drb_register_form_routes' );

function drb_proxy_booking_otp( WP_REST_Request $request, $action ) {
    if ( ! drb_verify_public_form_nonce( $request ) ) {
        return new WP_Error( 'invalid_nonce', drb_form_error_message( 'نشست فرم منقضی شده است. صفحه را تازه‌سازی کنید.' ), array( 'status' => 403 ) );
    }
    $secret = drb_booking_bridge_secret();
    if ( '' === $secret ) {
        return new WP_Error( 'booking_bridge_unconfigured', drb_form_error_message( 'سامانه تأیید شماره همراه موقتاً در دسترس نیست.' ), array( 'status' => 503 ) );
    }
    $data = (array) $request->get_json_params();
    $mobile = drb_normalize_mobile( $data['mobile'] ?? '' );
    $payload = array( 'mobile' => $mobile );
    if ( 'verify' === $action ) {
        $payload['otp'] = preg_replace( '/\D+/', '', drb_ascii_digits( (string) ( $data['otp'] ?? '' ) ) );
    }
    if ( ! $mobile ) {
        return new WP_Error( 'invalid_mobile', drb_form_error_message( 'شماره موبایل معتبر نیست.' ), array( 'status' => 422 ) );
    }
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    $rate_key = 'drb_booking_otp_' . hash( 'sha256', $ip . '|' . $mobile . '|' . $action );
    $count = (int) get_transient( $rate_key );
    if ( $count >= ( 'send' === $action ? 4 : 8 ) ) {
        return new WP_Error( 'rate_limited', drb_form_error_message( 'تعداد تلاش‌ها بیش از حد مجاز است. کمی بعد دوباره امتحان کنید.' ), array( 'status' => 429 ) );
    }
    set_transient( $rate_key, $count + 1, 10 * MINUTE_IN_SECONDS );
    $response = wp_remote_post( drb_dashboard_api_base() . '/bookings/otp/' . $action, array(
        'timeout' => 20, 'redirection' => 0, 'sslverify' => true,
        'reject_unsafe_urls' => true, 'httpversion' => '1.1',
        'headers' => array(
            'Accept' => 'application/json', 'Content-Type' => 'application/json; charset=utf-8',
            'X-WordPress-Bridge-Secret' => $secret,
        ),
        'body' => wp_json_encode( $payload ),
    ) );
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'booking_otp_transport', drb_form_error_message( 'ارتباط با سامانه پیامک برقرار نشد.' ), array( 'status' => 503 ) );
    }
    $status = (int) wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    if ( $status < 200 || $status >= 300 || ! is_array( $decoded ) || empty( $decoded['ok'] ) ) {
        $message = is_array( $decoded ) && ! empty( $decoded['errors'][0]['message'] )
            ? sanitize_text_field( (string) $decoded['errors'][0]['message'] )
            : drb_form_error_message( 'تأیید شماره همراه انجام نشد.' );
        return new WP_Error( 'booking_otp_failed', $message, array( 'status' => $status >= 400 ? $status : 502 ) );
    }
    return new WP_REST_Response( array(
        'success' => true,
        'message' => sanitize_text_field( (string) ( $decoded['data']['message'] ?? 'شماره همراه تأیید شد.' ) ),
        'verificationToken' => sanitize_text_field( (string) ( $decoded['data']['verification_token'] ?? '' ) ),
        'expiresIn' => (int) ( $decoded['data']['expires_in'] ?? 0 ),
    ), $status );
}

function drb_normalize_mobile( $raw ) {
    $digits = strtr( (string) $raw, array(
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ) );
    $digits = preg_replace( '/\D+/', '', $digits );
    if ( 0 === strpos( $digits, '0098' ) ) $digits = '0' . substr( $digits, 4 );
    elseif ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) $digits = '0' . substr( $digits, 2 );
    elseif ( 10 === strlen( $digits ) && '9' === $digits[0] ) $digits = '0' . $digits;
    return preg_match( '/^09\d{9}$/', $digits ) ? $digits : '';
}

function drb_is_valid_national_id( $raw ) {
    $id = preg_replace( '/\D+/', '', drb_ascii_digits( (string) $raw ) );
    if ( ! preg_match( '/^\d{10}$/', $id ) || preg_match( '/^(\d)\1{9}$/', $id ) ) return false;
    $sum = 0;
    for ( $i = 0; $i < 9; $i++ ) $sum += (int) $id[ $i ] * ( 10 - $i );
    $remainder = $sum % 11;
    $expected = $remainder < 2 ? $remainder : 11 - $remainder;
    return (int) $id[9] === $expected;
}

/**
 * International mobile normalizer for non-Persian booking.
 *
 * Accepts a phone with a country code (e.g. +1 404 555 0199, 0044 7..., 971 50 ...)
 * and returns it in E.164-ish storage form without the leading +. Returns an
 * empty string when the number is not plausible (7–15 digits, must start with
 * a valid country code when prefixed). This keeps the FA-only drb_normalize_mobile()
 * flow intact for Persian pages while unblocking international patients.
 */
function drb_normalize_mobile_intl( $raw ) {
    $digits = strtr( (string) $raw, array(
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ) );
    // Strip everything but digits and a leading plus.
    $plus = 0 === strpos( ltrim( $raw ), '+' ) || false !== strpos( (string) $raw, '+' );
    $digits = preg_replace( '/\D+/', '', $digits );
    if ( '' === $digits ) {
        return '';
    }
    // A leading 00 international prefix becomes the bare country code.
    if ( 0 === strpos( $digits, '00' ) ) {
        $digits = substr( $digits, 2 );
        $plus   = true;
    }
    // If it is actually an Iranian number submitted on a non-FA page, accept it
    // in the canonical 09xxxxxxxxx form so downstream SMS routing is unchanged.
    if ( preg_match( '/^09\d{9}$/', $digits ) ) {
        return $digits;
    }
    if ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
        return '0' . substr( $digits, 2 );
    }
    // Otherwise require a country code (2–3 leading digits) and 7–15 total.
    if ( ! $plus && 0 === strpos( $digits, '0' ) ) {
        // Bare national number with a leading 0 is ambiguous internationally.
        return '';
    }
    if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
        return '';
    }
    return $digits;
}

/**
 * True when the current request is for a non-Persian language (en/ar/tr/ru/fr/de/es).
 * Used to relax Iranian-only mobile validation for international patients.
 */
function drb_is_non_fa_lang() {
    return function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang();
}

function drb_dashboard_api_base() {
    if ( defined( 'DRB_DASHBOARD_API_BASE' ) && DRB_DASHBOARD_API_BASE ) {
        return untrailingslashit( DRB_DASHBOARD_API_BASE );
    }
    $env = getenv( 'DRB_DASHBOARD_API_BASE' );
    return $env ? untrailingslashit( $env ) : 'https://dashboard.drbastaninejad.com/api/v1';
}

function drb_booking_bridge_secret() {
    if ( defined( 'DRB_WORDPRESS_BRIDGE_SECRET' ) && DRB_WORDPRESS_BRIDGE_SECRET ) return (string) DRB_WORDPRESS_BRIDGE_SECRET;
    $env = getenv( 'WORDPRESS_BRIDGE_SECRET' );
    return $env ? (string) $env : '';
}

function drb_booking_cooldown_minutes() {
    return max( 1, min( 1440, (int) get_option( 'drb_booking_cooldown_minutes', 30 ) ) );
}

/**
 * Booking bridge timeout. The dashboard commits the booking before it attempts
 * SMS delivery, so WordPress must allow enough time for a slow provider response
 * while still failing closed on network problems. Can be overridden in wp-config.
 */
function drb_booking_bridge_timeout() {
    $timeout = defined( 'DRB_BOOKING_BRIDGE_TIMEOUT' ) ? (int) DRB_BOOKING_BRIDGE_TIMEOUT : 25;
    return max( 10, min( 60, $timeout ) );
}

/**
 * Classify WP HTTP transport errors without exposing host internals to patients.
 * This is also used by the admin diagnostics page.
 */
function drb_booking_transport_classification( WP_Error $error ) {
    $message = strtolower( (string) $error->get_error_message() );
    if ( false !== strpos( $message, 'curl error 60' ) || false !== strpos( $message, 'certificate' ) || false !== strpos( $message, 'ssl' ) ) {
        return 'tls';
    }
    if ( false !== strpos( $message, 'curl error 6' ) || false !== strpos( $message, 'resolve host' ) || false !== strpos( $message, 'name_not_resolved' ) ) {
        return 'dns';
    }
    if ( false !== strpos( $message, 'curl error 28' ) || false !== strpos( $message, 'timed out' ) || false !== strpos( $message, 'timeout' ) ) {
        return 'timeout';
    }
    if ( false !== strpos( $message, 'curl error 7' ) || false !== strpos( $message, 'failed to connect' ) || false !== strpos( $message, 'connection refused' ) ) {
        return 'connect';
    }
    return 'transport';
}

function drb_booking_transport_public_error( WP_Error $error ) {
    $class = drb_booking_transport_classification( $error );
    if ( 'tls' === $class ) {
        return array( 'code' => 'booking_bridge_tls', 'status' => 503, 'support' => 'BRIDGE-TLS', 'message' => 'اتصال امن سامانه نوبت‌دهی برقرار نشد. لطفاً با پذیرش تماس بگیرید.' );
    }
    if ( 'dns' === $class ) {
        return array( 'code' => 'booking_bridge_dns', 'status' => 503, 'support' => 'BRIDGE-DNS', 'message' => 'سامانه نوبت‌دهی در حال حاضر از سرور سایت قابل دسترسی نیست. لطفاً با پذیرش تماس بگیرید.' );
    }
    if ( 'timeout' === $class ) {
        return array( 'code' => 'booking_bridge_timeout', 'status' => 504, 'support' => 'BRIDGE-TIMEOUT', 'message' => 'پاسخ سامانه نوبت‌دهی طول کشید. برای جلوگیری از ثبت تکراری، فعلاً دوباره فرم را ارسال نکنید.' );
    }
    if ( 'connect' === $class ) {
        return array( 'code' => 'booking_bridge_connect', 'status' => 503, 'support' => 'BRIDGE-CONNECT', 'message' => 'ارتباط سرور سایت با سامانه نوبت‌دهی برقرار نشد. لطفاً با پذیرش تماس بگیرید.' );
    }
    return array( 'code' => 'booking_bridge_failed', 'status' => 503, 'support' => 'BRIDGE-HTTP', 'message' => 'ثبت درخواست نوبت انجام نشد. لطفاً دوباره تلاش کنید.' );
}

function drb_verify_public_form_nonce( WP_REST_Request $request ) {
    $nonce = (string) $request->get_header( 'X-DRB-Form-Nonce' );
    if ( $nonce && wp_verify_nonce( $nonce, 'drb_public_form' ) ) {
        return true;
    }
    $param = (string) $request->get_param( '_wpnonce' );
    return $param && wp_verify_nonce( $param, 'drb_public_form' );
}

function drb_form_error_message( $source ) {
    return function_exists( 'drb_translate_phrase' ) ? drb_translate_phrase( (string) $source ) : (string) $source;
}

function drb_is_revision_booking( array $data, $procedure ) {
    $key = sanitize_key( $data['procedureKey'] ?? $data['procedure_key'] ?? '' );
    if ( in_array( $key, array( 'revision', 'revision-rhinoplasty', 'secondary-rhinoplasty' ), true ) ) return true;
    $text = mb_strtolower( (string) $procedure );
    foreach ( array( 'ترمیم', 'ترميم', 'revision', 'revizyon', 'ревиз', 'повторн', 'révision', 'revisión' ) as $needle ) {
        if ( false !== mb_strpos( $text, $needle ) ) return true;
    }
    return false;
}

function drb_process_submission( WP_REST_Request $request, $kind ) {
    if ( ! drb_verify_public_form_nonce( $request ) ) {
        return new WP_Error( 'invalid_nonce', drb_form_error_message( 'نشست فرم منقضی شده است. صفحه را تازه‌سازی کنید.' ), array( 'status' => 403 ) );
    }

    $data = (array) $request->get_json_params();
    if ( ! $data ) {
        $data = (array) $request->get_body_params();
    }
    if ( ! $data ) {
        $data = (array) $request->get_params();
    }
    if ( ! empty( $data['website'] ) ) return new WP_Error( 'spam', drb_form_error_message( 'درخواست نامعتبر است.' ), array( 'status' => 400 ) );

    $first_name = sanitize_text_field( $data['firstName'] ?? $data['first_name'] ?? '' );
    $last_name = sanitize_text_field( $data['lastName'] ?? $data['last_name'] ?? '' );
    $name   = sanitize_text_field( $data['name'] ?? $data['fullName'] ?? trim( $first_name . ' ' . $last_name ) );
    $is_non_fa = drb_is_non_fa_lang();
    $country = sanitize_text_field( $data['country'] ?? '' );
    $dial_code = preg_replace( '/[^+0-9]/', '', drb_ascii_digits( (string) ( $data['dialCode'] ?? $data['dial_code'] ?? '' ) ) );
    $phone_raw = (string) ( $data['phone'] ?? $data['mobile'] ?? '' );
    // The international UI supplies country + dial code separately. Combine a
    // national number with that code before normalisation while preserving an
    // already-E.164 number exactly as entered.
    if ( $is_non_fa && preg_match( '/^\+\d{1,4}$/', $dial_code ) && ! preg_match( '/^\s*(?:\+|00)/', $phone_raw ) ) {
        $national_digits = preg_replace( '/\D+/', '', drb_ascii_digits( $phone_raw ) );
        $phone_raw = $dial_code . ltrim( $national_digits, '0' );
    }
    $mobile = $is_non_fa ? drb_normalize_mobile_intl( $phone_raw ) : drb_normalize_mobile( $phone_raw );
    $email  = sanitize_email( $data['email'] ?? '' );
    if ( mb_strlen( $name ) < 2 || ! $mobile ) {
        return new WP_Error( 'invalid_fields', drb_form_error_message( 'نام و شماره موبایل معتبر الزامی است.' ), array( 'status' => 422 ) );
    }
    // International (non-FA) bookings must include a valid email so the clinic
    // can reach the patient even when SMS delivery to their country is unavailable.
    if ( $is_non_fa && mb_strlen( $country ) < 2 ) {
        return new WP_Error( 'country_required', drb_form_error_message( 'برای رزرو بین‌المللی انتخاب کشور الزامی است.' ), array( 'status' => 422 ) );
    }
    if ( $is_non_fa && ! preg_match( '/^\+\d{1,4}$/', $dial_code ) ) {
        return new WP_Error( 'dial_code_required', drb_form_error_message( 'برای رزرو بین‌المللی کد کشور معتبر الزامی است.' ), array( 'status' => 422 ) );
    }
    if ( $is_non_fa && ! $email ) {
        return new WP_Error( 'email_required', drb_form_error_message( 'برای رزرو بین‌المللی وارد کردن ایمیل معتبر الزامی است.' ), array( 'status' => 422 ) );
    }
    if ( $email && ! is_email( $email ) ) return new WP_Error( 'invalid_email', drb_form_error_message( 'ایمیل معتبر نیست.' ), array( 'status' => 422 ) );

    if ( 'appointment' === $kind ) {
        $to_latin = static function( $value ) {
            return strtr( (string) $value, array( '۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9' ) );
        };
        $procedure = sanitize_text_field( $data['procedure'] ?? $data['service'] ?? '' );
        if ( ! $is_non_fa ) {
            $birth = str_replace( '-', '/', $to_latin( $data['birthDateJalali'] ?? $data['birth_date_jalali'] ?? '' ) );
            $national_id = preg_replace( '/\D+/', '', $to_latin( $data['nationalId'] ?? $data['national_id'] ?? '' ) );
            $medical = sanitize_textarea_field( $data['medicalHistory'] ?? $data['medical_history'] ?? '' );
            $medications = sanitize_textarea_field( $data['medications'] ?? '' );
            $doctor_request = sanitize_textarea_field( $data['doctorRequest'] ?? $data['doctor_request'] ?? '' );
            $otp_token = sanitize_text_field( $data['otpToken'] ?? $data['otp_token'] ?? '' );
            if ( mb_strlen( $first_name ) < 2 || mb_strlen( $last_name ) < 2 ) {
                return new WP_Error( 'name_required', drb_form_error_message( 'نام و نام خانوادگی معتبر الزامی است.' ), array( 'status' => 422 ) );
            }
            if ( ! preg_match( '/^(?:12|13|14|15)\d{2}\/(?:0?[1-9]|1[0-2])\/(?:0?[1-9]|[12]\d|3[01])$/', $birth ) ) {
                return new WP_Error( 'birth_required', drb_form_error_message( 'تاریخ تولد شمسی معتبر الزامی است.' ), array( 'status' => 422 ) );
            }
            if ( ! drb_is_valid_national_id( $national_id ) ) {
                return new WP_Error( 'national_id_required', drb_form_error_message( 'کد ملی معتبر نیست.' ), array( 'status' => 422 ) );
            }
            // Medical history, medications and a free-text doctor request are
            // collected later in the clinical intake when needed; they are not
            // prerequisites for reserving an appointment.
            if ( ! preg_match( '/^[a-f0-9]{64}$/', $otp_token ) ) {
                return new WP_Error( 'otp_required', drb_form_error_message( 'تأیید شماره همراه الزامی است.' ), array( 'status' => 422 ) );
            }
        } else {
            $age = absint( $to_latin( $data['age'] ?? 0 ) );
            if ( $age < 18 || $age > 45 ) {
                return new WP_Error( 'ineligible_age', drb_form_error_message( 'پذیرش جراحی فقط برای بازه سنی ۱۸ تا ۴۵ سال انجام می‌شود.' ), array( 'status' => 422 ) );
            }
        }
        return drb_proxy_appointment_to_dashboard( $data, $name, $mobile, $email, $procedure );
    }

    // Keep contact-form abuse protection isolated per validated mobile number.
    // Do not key public traffic by REMOTE_ADDR: behind the reverse proxy it is
    // shared by unrelated patients and can lock the entire clinic booking flow.
    $rate_key = 'drb_contact_rate_' . hash( 'sha256', $mobile );
    $count = (int) get_transient( $rate_key );
    if ( $count >= 5 ) {
        return new WP_Error( 'rate_limited', drb_form_error_message( 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.' ), array( 'status' => 429 ) );
    }
    set_transient( $rate_key, $count + 1, 10 * MINUTE_IN_SECONDS );

    // General contact messages may remain in WordPress. Clinical booking data may not.
    $allowed = array( 'name', 'fullName', 'phone', 'email', 'subject', 'message' );
    $clean = array();
    foreach ( $allowed as $key ) if ( isset( $data[ $key ] ) ) $clean[ $key ] = sanitize_textarea_field( (string) $data[ $key ] );
    $body = '';
    foreach ( $clean as $key => $value ) $body .= '<p><strong>' . esc_html( $key ) . ':</strong> ' . nl2br( esc_html( $value ) ) . '</p>';
    $post_id = wp_insert_post( array( 'post_type' => 'drb_submission', 'post_status' => 'private', 'post_title' => 'پیام تماس — ' . $name, 'post_content' => $body ) );
    if ( is_wp_error( $post_id ) || ! $post_id ) return new WP_Error( 'save_failed', drb_form_error_message( 'ثبت پیام انجام نشد.' ), array( 'status' => 500 ) );
    update_post_meta( $post_id, '_drb_submission_type', 'contact' );
    update_post_meta( $post_id, '_drb_phone', $mobile );
    if ( $email ) update_post_meta( $post_id, '_drb_email', $email );
    wp_mail( get_option( 'admin_email' ), 'پیام تماس جدید: ' . $name, wp_strip_all_tags( $body ) );
    do_action( 'drb_submission_created', $post_id, 'contact', $clean );
    return new WP_REST_Response( array( 'success' => true, 'message' => drb_form_error_message( 'پیام شما با موفقیت ثبت شد.' ), 'submissionId' => $post_id ), 201 );
}

function drb_proxy_appointment_to_dashboard( array $data, $name, $mobile, $email, $procedure ) {
    $secret = drb_booking_bridge_secret();
    if ( '' === $secret ) {
        error_log( '[DRB booking] WORDPRESS_BRIDGE_SECRET is not configured.' );
        return new WP_Error( 'booking_bridge_unconfigured', drb_form_error_message( 'سامانه نوبت‌دهی موقتاً در دسترس نیست.' ), array( 'status' => 503 ) );
    }

    $cooldown = drb_booking_cooldown_minutes();
    $mobile_key = 'drb_booking_mobile_' . hash( 'sha256', $mobile );
    if ( get_transient( $mobile_key ) ) {
        return new WP_Error( 'booking_cooldown', drb_form_error_message( 'درخواست این شماره به‌تازگی ثبت شده است. برای جلوگیری از ثبت تکراری کمی بعد دوباره تلاش کنید.' ), array( 'status' => 429, 'patientLogin' => 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html' ) );
    }
    // Short in-flight lock closes rapid parallel-submit races. It is replaced by
    // the configured cooldown only after the dashboard confirms the write.
    set_transient( $mobile_key, 'inflight', MINUTE_IN_SECONDS );

    $payload = array(
        'submission_uuid' => wp_generate_uuid4(),
        'name' => $name,
        'first_name' => sanitize_text_field( $data['firstName'] ?? $data['first_name'] ?? '' ),
        'last_name' => sanitize_text_field( $data['lastName'] ?? $data['last_name'] ?? '' ),
        'mobile' => $mobile,
        'email' => $email,
        'country' => sanitize_text_field( $data['country'] ?? '' ),
        'dial_code' => sanitize_text_field( $data['dialCode'] ?? $data['dial_code'] ?? '' ),
        'procedure' => $procedure,
        'procedure_key' => sanitize_key( $data['procedureKey'] ?? $data['procedure_key'] ?? '' ),
        'message' => sanitize_textarea_field( $data['message'] ?? $data['notes'] ?? '' ),
        'birth_date_jalali' => sanitize_text_field( $data['birthDateJalali'] ?? $data['birth_date_jalali'] ?? '' ),
        'national_id' => sanitize_text_field( $data['nationalId'] ?? $data['national_id'] ?? '' ),
        'medical_history' => sanitize_textarea_field( $data['medicalHistory'] ?? $data['medical_history'] ?? '' ),
        'medications' => sanitize_textarea_field( $data['medications'] ?? '' ),
        'doctor_request' => sanitize_textarea_field( $data['doctorRequest'] ?? $data['doctor_request'] ?? '' ),
        'otp_token' => sanitize_text_field( $data['otpToken'] ?? $data['otp_token'] ?? '' ),
        'age' => absint( strtr( (string) ( $data['age'] ?? 0 ), array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9' ) ) ),
        'previous_surgery_months' => absint( strtr( (string) ( $data['previousSurgeryMonths'] ?? 0 ), array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9' ) ) ),
        'source' => 'wordpress_booking',
        'language' => function_exists( 'drb_detect_lang' ) ? drb_detect_lang() : 'fa',
    );

    $response = wp_remote_post( drb_dashboard_api_base() . '/bookings', array(
        'timeout' => drb_booking_bridge_timeout(),
        'redirection' => 0,
        'sslverify' => true,
        'reject_unsafe_urls' => true,
        'httpversion' => '1.1',
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
            'X-WordPress-Bridge-Secret' => $secret,
            'X-DRB-Submission-UUID' => $payload['submission_uuid'],
        ),
        'body' => wp_json_encode( $payload ),
    ) );

    if ( is_wp_error( $response ) ) {
        $transport = drb_booking_transport_public_error( $response );
        // A timeout is ambiguous: the dashboard may already have committed the
        // booking and merely be waiting on SMS. Keep a short safety lock so a
        // patient cannot immediately create a duplicate by clicking again.
        if ( 'timeout' === drb_booking_transport_classification( $response ) ) {
            set_transient( $mobile_key, 'unknown_after_timeout', 10 * MINUTE_IN_SECONDS );
        } else {
            delete_transient( $mobile_key );
        }
        error_log( '[DRB booking][' . $transport['support'] . '] Dashboard request failed: ' . $response->get_error_message() );
        return new WP_Error( $transport['code'], drb_form_error_message( $transport['message'] ), array(
            'status' => $transport['status'],
            'supportCode' => $transport['support'],
        ) );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    if ( $status < 200 || $status >= 300 || ! is_array( $decoded ) || empty( $decoded['ok'] ) ) {
        delete_transient( $mobile_key );
        $remote_has_message = is_array( $decoded ) && ! empty( $decoded['errors'][0]['message'] );
        $is_duplicate = in_array( $status, array( 409, 429 ), true );
        if ( $remote_has_message ) {
            $remote_message = drb_form_error_message( sanitize_text_field( (string) $decoded['errors'][0]['message'] ) );
        } elseif ( $is_duplicate ) {
            $remote_message = drb_form_error_message( 'درخواست این شماره قبلاً ثبت شده است. لطفاً وارد پنل بیمار شوید.' );
        } else {
            $remote_message = drb_form_error_message( 'ثبت درخواست نوبت انجام نشد.' );
        }
        error_log( '[DRB booking] Dashboard rejected request; HTTP ' . $status );
        $error_code = $is_duplicate ? 'booking_already_exists' : 'booking_bridge_rejected';
        return new WP_Error( $error_code, sanitize_text_field( $remote_message ), array(
            'status' => ( $status >= 400 && $status < 600 ? $status : 502 ),
            'patientLogin' => in_array( $status, array( 409, 429 ), true ) ? 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html' : '',
        ) );
    }

    set_transient( $mobile_key, 'confirmed', $cooldown * MINUTE_IN_SECONDS );
    $sms_status = sanitize_key( $decoded['data']['sms_status'] ?? '' );
    if ( in_array( $sms_status, array( 'sent', 'delivered', 'success', 'successful' ), true ) ) {
        $success_message = 'درخواست نوبت دریافت شد؛ زمان نوبت هنوز قطعی نیست. همکاران کلینیک برای اعلام و تأیید زمان با شما تماس می‌گیرند. پیامک ثبت درخواست نیز ارسال شد.';
    } elseif ( in_array( $sms_status, array( 'failed', 'error', 'rejected', 'undelivered' ), true ) ) {
        // Booking is already committed by the dashboard. SMS failure must never
        // make the browser retry and accidentally create a second appointment.
        $success_message = 'درخواست نوبت دریافت شد؛ زمان نوبت هنوز قطعی نیست. ارسال پیامک ناموفق بود، اما همکاران کلینیک برای اعلام و تأیید زمان تماس می‌گیرند.';
    } else {
        $success_message = 'درخواست نوبت دریافت شد؛ این ثبت به معنی نوبت قطعی نیست. همکاران کلینیک برای اعلام و تأیید زمان تماس می‌گیرند.';
    }
    return new WP_REST_Response( array(
        'success' => true,
        'message' => drb_form_error_message( $success_message ),
        'bookingId' => (int) ( $decoded['data']['booking_id'] ?? 0 ),
        'smsStatus' => $sms_status,
    ), $status );
}

/** Non-PII booking/SMS counters for WordPress admin. */
function drb_dashboard_booking_stats() {
    $secret = drb_booking_bridge_secret();
    if ( '' === $secret ) return new WP_Error( 'bridge_unconfigured', drb_form_error_message( 'پل داشبورد تنظیم نشده است.' ) );
    $response = wp_remote_get( drb_dashboard_api_base() . '/bookings/stats', array(
        'timeout' => min( 15, drb_booking_bridge_timeout() ),
        'redirection' => 0,
        'sslverify' => true,
        'reject_unsafe_urls' => true,
        'httpversion' => '1.1',
        'headers' => array( 'Accept' => 'application/json', 'X-WordPress-Bridge-Secret' => $secret ),
    ) );
    if ( is_wp_error( $response ) ) {
        $transport = drb_booking_transport_public_error( $response );
        return new WP_Error( $transport['code'], $transport['message'], array(
            'supportCode' => $transport['support'],
            'technicalMessage' => $response->get_error_message(),
        ) );
    }
    $status = (int) wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    if ( 200 !== $status || ! is_array( $decoded ) || empty( $decoded['ok'] ) || ! is_array( $decoded['data'] ?? null ) ) {
        $remote_message = is_array( $decoded ) && ! empty( $decoded['errors'][0]['message'] )
            ? sanitize_text_field( (string) $decoded['errors'][0]['message'] )
            : '';
        return new WP_Error( 'bridge_stats_failed', drb_form_error_message( 'دریافت آمار از داشبورد انجام نشد.' ), array(
            'supportCode' => 'BRIDGE-HTTP-' . $status,
            'technicalMessage' => 'Dashboard HTTP ' . $status . ( $remote_message ? ': ' . $remote_message : '' ),
        ) );
    }
    return array_map( 'intval', $decoded['data'] );
}
