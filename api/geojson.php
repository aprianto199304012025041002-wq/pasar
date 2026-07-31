<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$q          = trim($_GET['q']          ?? '');
$service    = trim($_GET['service']    ?? '');
$subservice = trim($_GET['subservice'] ?? '');
$province   = trim($_GET['province']   ?? '');
$city       = trim($_GET['city']       ?? '');
$district   = trim($_GET['district']   ?? '');

$where  = ["sid_lat IS NOT NULL AND sid_long IS NOT NULL",
           "sid_lat BETWEEN -11 AND 6 AND sid_long BETWEEN 95 AND 141"];
$params = [];

if ($q !== '') {
    $like     = '%' . $q . '%';
    $where[]  = "(clnt_name LIKE ? OR curr_lic_num LIKE ? OR stn_name LIKE ? OR CAST(freq AS CHAR) LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($service    !== '') { $where[] = "service = ?";    $params[] = $service;    }
if ($subservice !== '') { $where[] = "subservice = ?"; $params[] = $subservice; }
if ($province   !== '') { $where[] = "province = ?";   $params[] = $province;   }
if ($city       !== '') { $where[] = "city = ?";       $params[] = $city;       }
if ($district   !== '') { $where[] = "district = ?";   $params[] = $district;   }

// Hard limit — cegah respons JSON terlalu besar jika data ribuan tanpa filter
define('GEOJSON_LIMIT', 5000);

// Hitung total dulu untuk tahu apakah hasil akan terpotong
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr WHERE " . implode(' AND ', $where));
$count_stmt->execute($params);
$total_matched = (int) $count_stmt->fetchColumn();
$truncated     = $total_matched > GEOJSON_LIMIT;

$sql = "SELECT sid_lat, sid_long, service, clnt_name, stn_name,
               freq, city, district, curr_lic_num, id
        FROM isr
        WHERE " . implode(' AND ', $where) . "
        ORDER BY id ASC
        LIMIT " . GEOJSON_LIMIT;

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
    'type'      => 'FeatureCollection',
    'features'  => $features,
    'total'     => $total_matched,
    'shown'     => count($features),
    'truncated' => $truncated,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);