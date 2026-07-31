<?php
require_once __DIR__ . '/includes/auth.php';

_session_start();

// === DEBUG MODE — HAPUS SETELAH SELESAI ===
$debug = [];
$debug['session_id']        = session_id();
$debug['session_data']      = $_SESSION;
$debug['get_params']        = array_keys($_GET);
$debug['token_present']     = !empty($_GET['token']);
$debug['token_length']      = strlen($_GET['token'] ?? '');
$debug['state_get']         = substr($_GET['state'] ?? '', 0, 8) . '...';
$debug['state_session']     = substr($_SESSION['sso_state'] ?? '', 0, 8) . '...';
$debug['state_match']       = isset($_GET['state'], $_SESSION['sso_state'])
                              && hash_equals($_SESSION['sso_state'], $_GET['state']);
$debug['state_age_sec']     = isset($_SESSION['sso_state_ts'])
                              ? (time() - $_SESSION['sso_state_ts']) : 'no_ts';
$debug['SSO_PUBLIC_KEY_defined'] = defined('SSO_PUBLIC_KEY');
$debug['SSO_PUBLIC_KEY_length']  = defined('SSO_PUBLIC_KEY') ? strlen(SSO_PUBLIC_KEY) : 0;
$debug['SSO_PUBLIC_KEY_head']    = defined('SSO_PUBLIC_KEY') ? substr(SSO_PUBLIC_KEY, 0, 40) : 'NOT DEFINED';

// Decode token header tanpa verifikasi
if (!empty($_GET['token'])) {
    $parts = explode('.', $_GET['token']);
    $hdr   = json_decode(base64_decode(strtr($parts[0] ?? '', '-_', '+/')), true);
    $body  = json_decode(base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true);
    $debug['token_alg']   = $hdr['alg'] ?? '?';
    $debug['token_iss']   = $body['iss'] ?? '?';
    $debug['token_exp']   = isset($body['exp']) ? date('Y-m-d H:i:s', $body['exp']) : '?';
    $debug['token_valid_time'] = isset($body['exp']) ? ($body['exp'] > time() ? 'OK' : 'EXPIRED') : '?';
    $debug['token_sub']   = $body['sub'] ?? '?';
}

// Coba verify token
if (!empty($_GET['token'])) {
    $result = verify_token($_GET['token']);
    $debug['verify_token_result'] = $result ? 'OK: ' . $result['user_id'] : 'FAILED';
}

header('Content-Type: application/json');
echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
// === END DEBUG ===
