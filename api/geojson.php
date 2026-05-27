<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$service = trim($_GET['service'] ?? '');
$allowed = ['Fixed Service','Land Mobile (public)','Land Mobile (private)','Broadcast','Aeronautical','Other Services'];
$params  = [];
$where   = "WHERE sid_lat IS NOT NULL AND sid_long IS NOT NULL
            AND sid_lat BETWEEN -11 AND 6 AND sid_long BETWEEN 95 AND 141";

if ($service !== '' && in_array($service, $allowed, true)) {
    $where   .= " AND service = ?";
    $params[] = $service;
}

$sql = "SELECT sid_lat, sid_long, service, clnt_name, stn_name,
               freq, city, district, curr_lic_num, id
        FROM isr $where
        ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$features = [];
foreach ($rows as $r) {
    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type'        => 'Point',
            'coordinates' => [(float)$r['sid_long'], (float)$r['sid_lat']],
        ],
        'properties' => [
            'id'       => $r['id'],
            'name'     => $r['clnt_name'],
            'stn'      => $r['stn_name'],
            'service'  => $r['service'],
            'freq'     => $r['freq'],
            'city'     => $r['city'],
            'district' => $r['district'],
            'lic'      => $r['curr_lic_num'],
        ],
    ];
}

echo json_encode([
    'type'     => 'FeatureCollection',
    'features' => $features,
    'total'    => count($features),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);