<?php
/**
 * SIMF Monitor — Konfigurasi
 * File ini hanya loader. Credential sebenarnya ada di config.local.php
 * config.local.php TIDAK masuk repo — set manual di server hosting.
 */

$local = __DIR__ . '/config.local.php';

if (!file_exists($local)) {
    http_response_code(503);
    die('Server configuration missing. Please create config.local.php on the server.');
}

require_once $local;
