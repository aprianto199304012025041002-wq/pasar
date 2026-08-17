<?php
require_once __DIR__ . '/includes/auth.php';

// Session params dikelola terpusat via _session_start() di auth.php
_session_start();

// Anti-replay: kalau sudah login, jangan proses token lagi
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
if (!$token) {
    error_log('[PASAR] Callback: token tidak ditemukan dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=1');
    exit;
}

// Validasi state parameter — cegah login CSRF
$state            = $_GET['state'] ?? '';
$session_state    = $_SESSION['sso_state'] ?? '';
$session_state_ts = $_SESSION['sso_state_ts'] ?? 0;

// Hapus state dari session terlepas dari hasilnya (one-time use)
unset($_SESSION['sso_state'], $_SESSION['sso_state_ts']);

if (!$state || !$session_state || !hash_equals($session_state, $state)) {
    error_log('[PASAR] Callback: state tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

// State expired setelah 10 menit
if (time() - $session_state_ts > 600) {
    error_log('[PASAR] Callback: state expired dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

$payload = verify_token($token);
if (!$payload) {
    error_log('[PASAR] Callback: token tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));

    // DEBUG SEMENTARA — tampilkan info token di layar
    $b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));
    $parts = explode('.', $token);
    $hdr = count($parts) >= 1 ? json_decode($b64d($parts[0]), true) : [];
    $pld = count($parts) >= 2 ? json_decode($b64d($parts[1]), true) : [];
    $pub_ok = defined('SSO_PUBLIC_KEY') ? (openssl_pkey_get_public(SSO_PUBLIC_KEY) ? 'OK' : 'GAGAL') : 'TIDAK DEFINED';
    $alg = $hdr['alg'] ?? '?';
    $exp = $pld['exp'] ?? 0;
    $iss = $pld['iss'] ?? '?';
    $expired = $exp < time() ? 'YA (exp='.date('H:i:s',$exp).' now='.date('H:i:s').')' : 'TIDAK';
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== DEBUG PASAR verify_token ===
";
    echo "alg: $alg
";
    echo "iss: $iss
";
    echo "expired: $expired
";
    echo "SSO_PUBLIC_KEY: $pub_ok
";
    echo "SSO_CLIENT_SECRET_PLAIN defined: " . (defined('SSO_CLIENT_SECRET_PLAIN') && SSO_CLIENT_SECRET_PLAIN ? 'YA' : 'TIDAK') . "
";
    exit;
}

// Regenerate session ID setelah login berhasil — cegah session fixation
session_regenerate_id(true);

$_SESSION['user_id']    = $payload['user_id'] ?? '';
$_SESSION['name']       = $payload['name']    ?? 'Pengguna';
$_SESSION['email']      = $payload['email']   ?? '';
$_SESSION['role']       = $payload['role']    ?? 'user';
$_SESSION['login_time'] = time();
$_SESSION['ip']         = $_SERVER['REMOTE_ADDR'] ?? '';

header('Location: ' . APP_BASE_URL . '/dashboard.php');
exit;
