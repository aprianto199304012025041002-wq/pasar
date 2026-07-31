<?php
require_once __DIR__ . '/includes/auth.php';
_session_start();

$debug = [];
$debug['state_match'] = isset($_GET['state'], $_SESSION['sso_state'])
                        && hash_equals($_SESSION['sso_state'], $_GET['state']);
$debug['state_age']   = time() - ($_SESSION['sso_state_ts'] ?? 0);

// Cek public key
$debug['pk_defined'] = defined('SSO_PUBLIC_KEY');
$debug['pk_length']  = defined('SSO_PUBLIC_KEY') ? strlen(SSO_PUBLIC_KEY) : 0;
$debug['pk_head']    = defined('SSO_PUBLIC_KEY') ? substr(trim(SSO_PUBLIC_KEY), 0, 26) : 'NOT_DEF';
$debug['pk_valid']   = false;
if (defined('SSO_PUBLIC_KEY')) {
    $pk = openssl_pkey_get_public(SSO_PUBLIC_KEY);
    $debug['pk_valid'] = ($pk !== false);
    if ($pk) {
        $info = openssl_pkey_get_details($pk);
        $debug['pk_bits'] = $info['bits'] ?? '?';
    } else {
        $debug['pk_openssl_error'] = openssl_error_string();
    }
}

// Token info
$token = $_GET['token'] ?? '';
if ($token) {
    $parts = explode('.', $token);
    $b64d = fn($d) => base64_decode(strtr($d, '-_', '+/') . str_repeat('=', (4-strlen($d)%4)%4));
    $hdr  = json_decode($b64d($parts[0] ?? ''), true);
    $body = json_decode($b64d($parts[1] ?? ''), true);
    $debug['alg']        = $hdr['alg'] ?? '?';
    $debug['iss']        = $body['iss'] ?? '?';
    $debug['exp_ok']     = isset($body['exp']) && $body['exp'] > time();
    $debug['exp_in_sec'] = isset($body['exp']) ? ($body['exp'] - time()) : '?';

    // Manual verify
    if (defined('SSO_PUBLIC_KEY') && ($pk = openssl_pkey_get_public(SSO_PUBLIC_KEY))) {
        $sig_raw = $b64d($parts[2] ?? '');
        $valid   = openssl_verify($parts[0].'.'.$parts[1], $sig_raw, $pk, OPENSSL_ALGO_SHA256);
        $debug['sig_verify'] = $valid; // 1=ok, 0=fail, -1=error
        $debug['sig_error']  = openssl_error_string() ?: 'none';
    }
}

unset($_SESSION['sso_state'], $_SESSION['sso_state_ts']);

header('Content-Type: application/json');
echo json_encode($debug, JSON_PRETTY_PRINT);
exit;
