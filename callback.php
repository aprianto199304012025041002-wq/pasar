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
    // DEBUG
    $b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));
    $parts = explode('.', $token);
    $hdr = count($parts) >= 1 ? json_decode($b64d($parts[0]), true) : [];
    $pld = count($parts) >= 2 ? json_decode($b64d($parts[1]), true) : [];
    $alg = $hdr['alg'] ?? '?';
    $exp = $pld['exp'] ?? 0;
    $iss = $pld['iss'] ?? '?';
    $pub_defined = defined('SSO_PUBLIC_KEY') ? 'YA' : 'TIDAK';
    $pub_ok = 'N/A';
    $verify_result = 'N/A';
    $openssl_err = '';
    if (defined('SSO_PUBLIC_KEY')) {
        $pub = openssl_pkey_get_public(SSO_PUBLIC_KEY);
        $pub_ok = $pub ? 'OK' : 'GAGAL';
        if ($pub && count($parts) === 3) {
            [$h,$b,$s] = $parts;
            $sig = $b64d($s);
            $verify_result = openssl_verify("$h.$b", $sig, $pub, OPENSSL_ALGO_SHA256);
            $openssl_err = openssl_error_string() ?: '';
        }
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo "alg: $alg
";
    echo "iss: $iss
";
    echo "exp: $exp | now: " . time() . " | expired: " . ($exp < time() ? 'YA' : 'TIDAK') . "
";
    echo "SSO_PUBLIC_KEY defined: $pub_defined
";
    echo "openssl_pkey_get_public: $pub_ok
";
    echo "openssl_verify result: $verify_result
";
    echo "openssl_error: $openssl_err
";
    echo "key preview: " . substr(defined('SSO_PUBLIC_KEY') ? SSO_PUBLIC_KEY : '', 0, 80) . "
";

    // Cek payload
    $raw = $b64d($parts[1] ?? '');
    echo "raw_payload: " . $raw . "
";
    $dec = json_decode($raw, true);
    echo "json_error: " . json_last_error_msg() . "
";
    if ($dec) {
        echo "iss: '" . ($dec['iss'] ?? 'NULL') . "' match: " . ((($dec['iss'] ?? '') === 'gudeg-sso') ? 'YA' : 'TIDAK') . "
";
        echo "exp ok: " . (($dec['exp'] ?? 0) >= time() ? 'YA' : 'TIDAK') . "
";
    }
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
