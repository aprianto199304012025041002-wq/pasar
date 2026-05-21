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
    $url = SSO_VERIFY_URL . '?' . http_build_query([
        'token'         => $token,
        'client_id'     => SSO_CLIENT_ID,
        'client_secret' => SSO_CLIENT_SECRET,
    ]);

    // Coba cURL dulu, fallback ke file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $response = @file_get_contents($url);
    }

    if (!$response) return false;
    $data = json_decode($response, true);
    if (!$data || empty($data['valid'])) return false;
    return $data['payload'] ?? false;
}
