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

$token = $_GET['token'] ?? '';
if (!$token) {
    error_log('[PASAR] Callback: token tidak ditemukan dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Location: ' . APP_BASE_URL . '/login.php?err=1');
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