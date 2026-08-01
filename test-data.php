<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$q = ''; $service = ''; $subservice = ''; $province = ''; $city = ''; $district = '';
$page_num = 1; $per_page = PER_PAGE;
$user_lat = null; $user_lng = null; $sort_dist = false;

$services = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$provinces = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);
$subservices = $pdo->query("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL ORDER BY subservice")->fetchAll(PDO::FETCH_COLUMN);
$cities = $pdo->query("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$districts = $pdo->query("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);

$where = []; $params = [];
$clause = '';
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr $clause");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset = 0;

$data_stmt = $pdo->prepare("SELECT id, clnt_name, service, subservice, curr_lic_num, stn_name, validity_date, freq, sid_lat, sid_long, NULL AS dist_km FROM isr ORDER BY clnt_name ASC LIMIT $per_page OFFSET $offset");
$data_stmt->execute([]);
$rows = $data_stmt->fetchAll();

function svc_badge(string $svc): string {
    return '<span class="badge">' . htmlspecialchars($svc) . '</span>';
}
function page_url(int $p): string { return '?page=' . $p; }
function fmt_dist(float $km): string { return $km < 1 ? round($km*1000).' m' : number_format($km,2,',','.').' km'; }

$page_title = 'Data ISR';

echo "CHECKPOINT 1: before header<br>";
require_once __DIR__ . '/includes/header.php';
echo "CHECKPOINT 2: after header<br>";
?>

<div class="page-title">Test Data ISR</div>
<div class="card">
  <?php echo "CHECKPOINT 3: in card<br>"; ?>
  <?php
  $active_filters = [];
  $filter_count = 0;
  $filter_summary = [];
  $summary_text = '';
  ?>
  <?php echo "CHECKPOINT 4: after filter summary<br>"; ?>
  <table><thead><tr><th>#</th><th>Nama</th></tr></thead><tbody>
  <?php foreach ($rows as $i => $row): ?>
  <tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($row['clnt_name']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php echo "CHECKPOINT 5: after table<br>"; ?>
</div>

<?php
echo "CHECKPOINT 6: before footer";
require_once __DIR__ . '/includes/footer.php';
?>
