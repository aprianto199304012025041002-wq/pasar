<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// Reset opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache_reset: OK<br>";
} else {
    echo "opcache tidak aktif<br>";
}

// Cek isi baris pertama data.php di server
$lines = file(__DIR__ . '/data.php');
echo "data.php baris 1: " . htmlspecialchars($lines[0]) . "<br>";
echo "data.php baris 2: " . htmlspecialchars($lines[1]) . "<br>";
echo "data.php baris 3: " . htmlspecialchars($lines[2]) . "<br>";
echo "data.php modified: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/data.php')) . "<br>";
echo "server time: " . date('Y-m-d H:i:s') . "<br>";
