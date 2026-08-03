<?php
require_once __DIR__ . '/includes/auth.php';

// FIX: gunakan _session_start() agar cookie params (secure, httponly, samesite)
// konsisten dengan yang diset saat login via callback.php.
// Sebelumnya session_start() tanpa params bisa menyebabkan cookie tidak terhapus
// dengan benar di browser karena mismatch atribut Set-Cookie.
_session_start();

session_unset();
session_destroy();

// Hapus cookie session dengan params yang sama persis saat dibuat
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
header('Location: ' . $base . '/login.php');
exit;
