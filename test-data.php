<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<pre>';

// Test 1: koneksi DB
try {
    $r = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();
    echo "DB OK: $r rows\n";
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

// Test 2: query dropdown (sama persis dengan data.php)
try {
    $services = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
    echo "services OK: " . count($services) . "\n";
} catch (Exception $e) {
    echo "services ERROR: " . $e->getMessage() . "\n";
}

// Test 3: query COUNT dengan clause kosong
try {
    $clause = '';
    $params = [];
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr $clause");
    $count_stmt->execute($params);
    echo "count OK: " . $count_stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "count ERROR: " . $e->getMessage() . "\n";
}

// Test 4: query data (sama persis)
try {
    $per_page = 25;
    $offset = 0;
    $data_stmt = $pdo->prepare("SELECT id, clnt_name, service, subservice, curr_lic_num, stn_name, validity_date, freq, sid_lat, sid_long, NULL AS dist_km FROM isr ORDER BY clnt_name ASC LIMIT $per_page OFFSET $offset");
    $data_stmt->execute([]);
    $rows = $data_stmt->fetchAll();
    echo "data OK: " . count($rows) . " rows\n";
} catch (Exception $e) {
    echo "data ERROR: " . $e->getMessage() . "\n";
}

// Test 5: require header (mungkin crash di sini)
echo "before header include\n";
ob_start();
try {
    require_once __DIR__ . '/includes/header.php';
    $hdr = ob_get_clean();
    echo "header OK: " . strlen($hdr) . " bytes\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "header ERROR: " . $e->getMessage() . "\n";
}

echo "ALL DONE\n";
echo '</pre>';
