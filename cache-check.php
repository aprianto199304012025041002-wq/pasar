<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

// Reset opcache dulu
if (function_exists('opcache_reset')) opcache_reset();

// Tangkap semua error
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<b>ERROR [$errno]:</b> $errstr in $errfile line $errline<br>";
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<b>FATAL:</b> {$e['message']} in {$e['file']} line {$e['line']}";
    }
});

// Simulasi semua variabel yang dibutuhkan data.php
$_GET = [];
$_SERVER['REQUEST_URI'] = '/data.php';

echo "<pre>Menjalankan data.php...</pre>";
ob_start();
include __DIR__ . '/data.php';
$out = ob_get_clean();
echo "<pre>Output length: " . strlen($out) . " bytes</pre>";
if (strlen($out) < 500) {
    echo "<pre>" . htmlspecialchars($out) . "</pre>";
} else {
    echo "<pre>Output OK (". strlen($out) ." bytes) — data.php berjalan normal</pre>";
}
