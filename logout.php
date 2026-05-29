<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Hapus cookie session secara eksplisit
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