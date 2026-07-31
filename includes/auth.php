<?php
require_once __DIR__ . '/../config.php';

function _session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    // Pastikan cookie params konsisten di semua titik session_start()
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function require_login() {
    _session_start();
    if (empty($_SESSION['user_id'])) {
        // Generate state token untuk cegah login CSRF
        $state = bin2hex(random_bytes(16));
        $_SESSION['sso_state']    = $state;
        $_SESSION['sso_state_ts'] = time();
        $login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID) . '&state=' . urlencode($state);
        header('Location: ' . $login_url);
        exit;
    }
}

function get_user(): array {
    _session_start();
    return [
        'id'    => $_SESSION['user_id']  ?? '',
        'name'  => $_SESSION['name']     ?? 'Pengguna',
        'email' => $_SESSION['email']    ?? '',
        'role'  => $_SESSION['role']     ?? 'user',
    ];
}

function is_logged_in(): bool {
    _session_start();
    return !empty($_SESSION['user_id']);
}

function verify_token(string $token): array|false {
    $b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));
    $b64e = fn($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

    // Pecah token
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    [$header, $body, $sig] = $parts;

    // Deteksi algoritma dari header
    $header_data = json_decode($b64d($header), true);
    $alg = $header_data['alg'] ?? 'HS256';

    if ($alg === 'RS256') {
        // Verifikasi RSA — pakai public key gudeg
        $public_key_pem = defined('SSO_PUBLIC_KEY') ? SSO_PUBLIC_KEY : false;
        if (!$public_key_pem) return false;

        $public_key = openssl_pkey_get_public($public_key_pem);
        if (!$public_key) return false;

        $signature = $b64d($sig);
        $valid = openssl_verify("$header.$body", $signature, $public_key, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) return false;

    } else {
        // Verifikasi HMAC — pakai client_secret_plain (hex plaintext, konsisten dengan sso.php GUDEG)
        // SSO_CLIENT_SECRET (bcrypt hash) hanya untuk password_verify(), bukan HMAC key
        if (!defined('SSO_CLIENT_SECRET_PLAIN') || !SSO_CLIENT_SECRET_PLAIN) return false;
        $expected = $b64e(hash_hmac('sha256', "$header.$body", SSO_CLIENT_SECRET_PLAIN, true));
        if (!hash_equals($expected, $sig)) return false;
    }

    // Decode payload
    $payload = json_decode($b64d($body), true);
    if (!$payload) return false;

    // Cek expiry
    if (($payload['exp'] ?? 0) < time()) return false;

    // Validasi issuer — tolak token yang bukan dari GUDEG SSO
    if (($payload['iss'] ?? '') !== 'gudeg-sso') return false;

    return [
        'user_id' => $payload['sub'],
        'name'    => $payload['name'],
        'email'   => $payload['email'],
        'role'    => $payload['role'],
        'exp'     => $payload['exp'],
    ];
}

