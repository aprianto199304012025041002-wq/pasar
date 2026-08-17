<?php
/**
 * DEBUG SEMENTARA — HAPUS SETELAH SELESAI
 * Akses: https://pasar.balmonjogja.info/debug-sso.php?token=<jwt_dari_gudeg>
 */
require_once __DIR__ . '/config.php';

// Proteksi minimal
if (($_GET['k'] ?? '') !== 'debug2026') {
    http_response_code(403); die('forbidden');
}

$token = $_GET['token'] ?? '';
if (!$token) die('no token — tambahkan ?k=debug2026&token=<jwt>');

header('Content-Type: text/plain');

$b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4 - strlen($d) % 4) % 4));

$parts = explode('.', $token);
echo "Parts: " . count($parts) . "\n";
if (count($parts) !== 3) die("Token format salah");

[$header, $body, $sig] = $parts;
$header_data = json_decode($b64d($header), true);
$payload     = json_decode($b64d($body), true);

echo "Header: " . json_encode($header_data) . "\n";
echo "Payload: " . json_encode($payload) . "\n";
echo "Exp: " . ($payload['exp'] ?? 0) . " | Now: " . time() . " | Expired: " . (($payload['exp'] ?? 0) < time() ? 'YA' : 'TIDAK') . "\n";
echo "Issuer: " . ($payload['iss'] ?? 'N/A') . "\n";
echo "Alg: " . ($header_data['alg'] ?? 'N/A') . "\n\n";

echo "SSO_PUBLIC_KEY defined: " . (defined('SSO_PUBLIC_KEY') ? 'YA' : 'TIDAK') . "\n";
if (defined('SSO_PUBLIC_KEY')) {
    $pub = openssl_pkey_get_public(SSO_PUBLIC_KEY);
    echo "openssl_pkey_get_public: " . ($pub ? 'OK' : 'GAGAL') . "\n";
    if ($pub) {
        $signature = $b64d($sig);
        $valid = openssl_verify("$header.$body", $signature, $pub, OPENSSL_ALGO_SHA256);
        echo "openssl_verify result: $valid (1=valid, 0=invalid, -1=error)\n";
        if ($valid === -1) echo "OpenSSL error: " . openssl_error_string() . "\n";
    }
}

echo "\nSSO_CLIENT_SECRET_PLAIN defined: " . (defined('SSO_CLIENT_SECRET_PLAIN') && SSO_CLIENT_SECRET_PLAIN ? 'YA' : 'TIDAK') . "\n";
