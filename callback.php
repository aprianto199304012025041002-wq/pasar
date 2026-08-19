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

// Fallback: WebView Android kadang tidak membawa session cookie saat
// cross-site redirect chain. Gunakan cookie sso_state jika session kosong.
$cookie_state = $_COOKIE['sso_state'] ?? '';
setcookie('sso_state', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);

if (!$state) {
    error_log('[PASAR] Callback: state GET param kosong dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

// Cek state: prioritaskan session, fallback ke cookie
if ($session_state && hash_equals($session_state, $state)) {
    // State valid via session — cek expiry
    if (time() - $session_state_ts > 600) {
        error_log('[PASAR] Callback: state expired (session) dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
        header('Location: ' . APP_BASE_URL . '/login.php?err=3');
        exit;
    }
} elseif ($cookie_state && hash_equals($cookie_state, $state)) {
    // State valid via cookie fallback (WebView cross-site redirect)
    error_log('[PASAR] Callback: state validated via cookie fallback dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
} else {
    error_log('[PASAR] Callback: state tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

$payload = verify_token($token);
if (!$payload) {
    error_log('[PASAR] Callback: token tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=2');
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
