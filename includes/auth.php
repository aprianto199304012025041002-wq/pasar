<?php
require_once __DIR__ . '/../config.php';

function require_login() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        $login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID);
        header('Location: ' . $login_url);
        exit;
    }
}

function get_user(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return [
        'id'    => $_SESSION['user_id']  ?? '',
        'name'  => $_SESSION['name']     ?? 'Pengguna',
        'email' => $_SESSION['email']    ?? '',
        'role'  => $_SESSION['role']     ?? 'user',
    ];
}

function is_logged_in(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['user_id']);
}

function verify_token(string $token): array|false {
    // Verifikasi lokal — tidak perlu hit server SSO
    $b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));
    $b64e = fn($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

    // Pecah token
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    [$header, $body, $sig] = $parts;

    // Verifikasi signature pakai shared secret
    $expected = $b64e(hash_hmac('sha256', "$header.$body", SSO_CLIENT_SECRET, true));
    if (!hash_equals($expected, $sig)) return false;

    // Decode payload
    $payload = json_decode($b64d($body), true);
    if (!$payload) return false;

    // Cek expiry
    if (($payload['exp'] ?? 0) < time()) return false;

    // Return format sama seperti response verify.php SSO
    return [
        'user_id' => $payload['sub'],
        'name'    => $payload['name'],
        'email'   => $payload['email'],
        'role'    => $payload['role'],
        'exp'     => $payload['exp'],
    ];
}