<?php
require_once __DIR__ . '/includes/auth.php';

// Pastikan session belum dimulai sebelum set cookie params
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Anti-replay: kalau sudah login, jangan proses token lagi
// Cegah token yang dicuri dipakai untuk hijack session yang sedang aktif
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
$state          = $_GET['state'] ?? '';
$session_state  = $_SESSION['sso_state'] ?? '';
$session_state_ts = $_SESSION['sso_state_ts'] ?? 0;

// Hapus state dari session terlepas dari hasilnya (one-time use)
unset($_SESSION['sso_state'], $_SESSION['sso_state_ts']);

if (!$state || !$session_state || !hash_equals($session_state, $state)) {
    error_log('[PASAR] Callback: state tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=3');
    exit;
}

// State expired setelah 10 menit (cegah reuse state lama)
if (time() - $session_state_ts > 600) {
    error_log('[PASAR] Callback: state expired dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
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
