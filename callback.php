<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token tidak ditemukan. <a href="login.php">Kembali ke login</a>');
}

$payload = verify_token($token);
if (!$payload) {
    die('Login gagal: token tidak valid atau server SSO tidak dapat dihubungi. <a href="login.php">Coba lagi</a>');
}

$_SESSION['user_id'] = $payload['user_id'] ?? $payload['id'] ?? '';
$_SESSION['name']    = $payload['name']    ?? 'Pengguna';
$_SESSION['email']   = $payload['email']   ?? '';
$_SESSION['role']    = $payload['role']    ?? 'user';

header('Location: ' . APP_BASE_URL . '/dashboard.php');
exit;