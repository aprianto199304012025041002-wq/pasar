<?php
require_once __DIR__ . '/includes/auth.php';

// === DEBUG SEMENTARA — hapus setelah masalah resolved ===
ini_set('display_errors', 0);
error_reporting(E_ALL);
$_debug = [];
$_t0 = microtime(true);
// =========================================================

// Session params dikelola terpusat via _session_start() di auth.php
_session_start();
$_debug[] = 'session_started: sid=' . session_id();

// Anti-replay: kalau sudah login, jangan proses token lagi
// Cegah token yang dicuri dipakai untuk hijack session yang sedang aktif
if (!empty(\$_SESSION['user_id'])) {
    error_log('[PASAR-DEBUG] Already logged in, redirect dashboard');
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}
\$_debug[] = 'not_logged_in: OK';

$token = $_GET['token'] ?? '';
if (!\$token) {
    error_log('[PASAR-DEBUG] No token in GET: ' . json_encode(\$_GET));
    header('Location: ' . APP_BASE_URL . '/login.php?err=1');
    exit;
}
\$_debug[] = 'token_present: len=' . strlen(\$token);

// Validasi state parameter — cegah login CSRF
$state          = $_GET['state'] ?? '';
$session_state  = $_SESSION['sso_state'] ?? '';
$session_state_ts = $_SESSION['sso_state_ts'] ?? 0;

// Hapus state dari session terlepas dari hasilnya (one-time use)
unset($_SESSION['sso_state'], $_SESSION['sso_state_ts']);

\$_debug[] = 'state_check: get=' . substr(\$state,0,8) . ' sess=' . substr(\$session_state,0,8);
if (!\$state || !\$session_state || !hash_equals(\$session_state, \$state)) {
    error_log('[PASAR-DEBUG] State mismatch: GET=' . \$state . ' SESSION=' . \$session_state);
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

// State expired setelah 10 menit (cegah reuse state lama)
if (time() - $session_state_ts > 600) {
    error_log('[PASAR] Callback: state expired dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

\$_debug[] = 'before_verify_token';
\$payload = verify_token(\$token);
if (!\$payload) {
    // Debug: decode token header untuk lihat alg
    \$parts = explode('.', \$token);
    \$hdr = json_decode(base64_decode(strtr(\$parts[0] ?? '', '-_', '+/')), true);
    error_log('[PASAR-DEBUG] verify_token FAILED. alg=' . (\$hdr['alg'] ?? '?') . ' SSO_PUBLIC_KEY_defined=' . (defined('SSO_PUBLIC_KEY') ? 'yes_len' . strlen(SSO_PUBLIC_KEY) : 'NO'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=2');
    exit;
}
\$_debug[] = 'verify_token: OK user=' . \$payload['user_id'];

// Regenerate session ID setelah login berhasil — cegah session fixation
\$_debug[] = 'before_regenerate';
session_regenerate_id(true);
\$_debug[] = 'after_regenerate: new_sid=' . session_id();

$_SESSION['user_id']    = $payload['user_id'] ?? '';
$_SESSION['name']       = $payload['name']    ?? 'Pengguna';
$_SESSION['email']      = $payload['email']   ?? '';
$_SESSION['role']       = $payload['role']    ?? 'user';
$_SESSION['login_time'] = time();
$_SESSION['ip']         = $_SERVER['REMOTE_ADDR'] ?? '';

error_log('[PASAR-DEBUG] Login success: ' . json_encode(\$_debug) . ' t=' . round((microtime(true)-\$_t0)*1000) . 'ms');
header('Location: ' . APP_BASE_URL . '/dashboard.php');
exit;

