<?php
// v2
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Data ISR';

// ── Input sanitize ────────────────────────────────────────────
$q               = trim($_GET['q']               ?? '');
$service         = trim($_GET['service']         ?? '');
$subservice      = trim($_GET['subservice']      ?? '');
$area_of_service = trim($_GET['area_of_service'] ?? '');
$province        = trim($_GET['province']        ?? '');
$city            = trim($_GET['city']            ?? '');
$district        = trim($_GET['district']        ?? '');
$page_num   = max(1, intval($_GET['page'] ?? 1));
$per_page   = PER_PAGE;

// Koordinat user untuk sort jarak
$user_lat   = isset($_GET['ulat']) && is_numeric($_GET['ulat']) ? floatval($_GET['ulat']) : null;
$user_lng   = isset($_GET['ulng']) && is_numeric($_GET['ulng']) ? floatval($_GET['ulng']) : null;
$sort_dist  = ($user_lat !== null && $user_lng !== null);

// ── Dropdown options ──────────────────────────────────────────
$services         = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$areas_of_service = $pdo->query("SELECT DISTINCT area_of_service FROM isr WHERE area_of_service IS NOT NULL ORDER BY area_of_service")->fetchAll(PDO::FETCH_COLUMN);
$provinces        = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);

if ($service !== '') {
    $ss = $pdo->prepare("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL AND service = ? ORDER BY subservice");
    $ss->execute([$service]);
} else {
    $ss = $pdo->query("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL ORDER BY subservice");
}
$subservices = $ss->fetchAll(PDO::FETCH_COLUMN);

if ($province !== '') {
    $cs = $pdo->prepare("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL AND province = ? ORDER BY city");
    $cs->execute([$province]);
} else {
    $cs = $pdo->query("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL ORDER BY city");
}
$cities = $cs->fetchAll(PDO::FETCH_COLUMN);

if ($city !== '') {
    $ds = $pdo->prepare("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL AND city = ? ORDER BY district");
    $ds->execute([$city]);
} else {
    $ds = $pdo->query("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL ORDER BY district");
}
$districts = $ds->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE ───────────────────────────────────────────────
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = "(clnt_name LIKE ? OR curr_lic_num LIKE ? OR stn_name LIKE ? OR CAST(freq AS CHAR) LIKE ?)";
    $like     = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($service         !== '') { $where[] = "service = ?";          $params[] = $service;         }
if ($subservice      !== '') { $where[] = "subservice = ?";       $params[] = $subservice;      }
if ($area_of_service !== '') { $where[] = "area_of_service = ?";  $params[] = $area_of_service; }
if ($province        !== '') { $where[] = "province = ?";         $params[] = $province;        }
if ($city            !== '') { $where[] = "city = ?";             $params[] = $city;            }
if ($district        !== '') { $where[] = "district = ?";         $params[] = $district;        }

// Kalau sort jarak, wajib ada koordinat valid
if ($sort_dist) {
    $where[] = "sid_lat IS NOT NULL AND sid_long IS NOT NULL";
    $where[] = "sid_lat BETWEEN -11 AND 6 AND sid_long BETWEEN 95 AND 141";
}

$clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Haversine expression (MySQL) ──────────────────────────────
// 6371 * 2 * ASIN(SQRT(POW(SIN((...)/2),2) + COS()*COS()*POW(SIN((...)/2),2)))
$haversine_expr = '';
$dist_params    = [];
if ($sort_dist) {
    $haversine_expr = "
        (6371 * 2 * ASIN(SQRT(
            POW(SIN((RADIANS(sid_lat) - RADIANS(?)) / 2), 2) +
            COS(RADIANS(?)) * COS(RADIANS(sid_lat)) *
            POW(SIN((RADIANS(sid_long) - RADIANS(?)) / 2), 2)
        ))) AS dist_km";
    $dist_params = [$user_lat, $user_lat, $user_lng];
}

// ── Count ─────────────────────────────────────────────────────
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr $clause");
$count_stmt->execute($params);
$total_rows  = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$page_num    = min($page_num, $total_pages);
$offset      = ($page_num - 1) * $per_page;

// ── Data ──────────────────────────────────────────────────────
$select_dist = $sort_dist ? ", $haversine_expr" : ", NULL AS dist_km";
$order_by    = $sort_dist ? "ORDER BY dist_km ASC" : "ORDER BY clnt_name ASC";

$data_sql = "SELECT id, clnt_name, service, subservice,
                    curr_lic_num, stn_name, validity_date, freq,
                    sid_lat, sid_long
                    $select_dist
             FROM isr $clause
             $order_by
             LIMIT $per_page OFFSET $offset";

// Parameter: dist_params muncul di SELECT sebelum WHERE params
$exec_params = $sort_dist ? array_merge($dist_params, $params) : $params;

$data_stmt = $pdo->prepare($data_sql);
$data_stmt->execute($exec_params);
$rows = $data_stmt->fetchAll();

// ── Helpers ───────────────────────────────────────────────────
function svc_badge(string $svc): string {
    $map = [
        'Fixed Service'         => 'badge-fixed',
        'Land Mobile (public)'  => 'badge-lm-pub',
        'Land Mobile (private)' => 'badge-lm-pri',
        'Broadcast'             => 'badge-broadcast',
        'Aeronautical'          => 'badge-aero',
    ];
    $cls = $map[$svc] ?? 'badge-other';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($svc) . '</span>';
}

function page_url(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

function fmt_dist(float $km): string {
    if ($km < 1) return number_format($km * 1000, 0) . ' m';
    return number_format($km, 2, ',', '.') . ' km';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Data ISR
  <div class="page-sub">Total <?= number_format($total_rows, 0, ',', '.') ?> record ditemukan<?= $sort_dist ? ' · diurutkan dari lokasi Anda' : '' ?></div>
</div>

<div class="card">

  <!-- Filter + Search -->
  <?php
  // Hitung filter aktif untuk summary di toggle bar
  $active_filters = array_filter([$q, $service, $subservice, $area_of_service, $province, $city, $district, $_GET['ulat'] ?? '']);
  $filter_count   = count($active_filters);
  $filter_summary = [];
  if ($q !== '')               $filter_summary[] = '"' . substr(htmlspecialchars($q), 0, 20) . (strlen(htmlspecialchars($q)) > 20 ? '…' : '') . '"';
  if ($service !== '')         $filter_summary[] = htmlspecialchars($service);
  if ($area_of_service !== '') $filter_summary[] = htmlspecialchars($area_of_service);
  if ($province !== '')        $filter_summary[] = htmlspecialchars($province);
  if ($city !== '')            $filter_summary[] = htmlspecialchars($city);
  if ($sort_dist)              $filter_summary[] = 'sortir jarak';
  $summary_text = implode(', ', array_slice($filter_summary, 0, 2)) . (count($filter_summary) > 2 ? ', …' : '');
  ?>
  <form method="get" action="" id="data-form">
    <!-- Field koordinat user (hidden, diisi JS) -->
    <input type="hidden" name="ulat" id="f-ulat" value="<?= htmlspecialchars($_GET['ulat'] ?? '') ?>">
    <input type="hidden" name="ulng" id="f-ulng" value="<?= htmlspecialchars($_GET['ulng'] ?? '') ?>">

    <div class="filter-compact" id="data-filter-compact">

      <!-- Toggle bar -->
      <div class="filter-toggle-bar" id="data-filter-toggle" role="button" aria-expanded="true">
        <span class="filter-toggle-label">
          <i class="ti ti-filter" style="font-size:13px;flex-shrink:0"></i>
          <span>Filter &amp; Pencarian</span>
          <?php if ($filter_count > 0): ?>
          <span class="filter-active-summary">&mdash; <?= $summary_text ?></span>
          <?php endif; ?>
        </span>
        <?php if ($filter_count > 0): ?>
        <span style="font-size:10px;background:var(--blue);color:#fff;border-radius:10px;padding:1px 6px;flex-shrink:0">
          <?= $filter_count ?>
        </span>
        <?php endif; ?>
        <i class="ti ti-chevron-down filter-toggle-chevron"></i>
      </div>

      <div class="filter-body">

      <!-- Baris 1: Search -->
      <div class="filter-search-row">
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input type="search" name="q" id="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Cari nama klien, stasiun, No. ISR, atau frekuensi (MHz)...">
        </div>
      </div>

      <!-- Baris 2: Dropdowns + actions -->
      <div class="filter-dropdowns-row">
        <div class="form-group">
          <label>Layanan</label>
          <select name="service" id="sel-service" class="auto-submit">
            <option value="">Semua layanan</option>
            <?php foreach ($services as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $service === $s ? 'selected' : '' ?>>
              <?= htmlspecialchars($s) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Sub-layanan</label>
          <select name="subservice" class="auto-submit">
            <option value="">Semua sub-layanan</option>
            <?php foreach ($subservices as $ss): ?>
            <option value="<?= htmlspecialchars($ss) ?>" <?= $subservice === $ss ? 'selected' : '' ?>>
              <?= htmlspecialchars($ss) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Wilayah Layanan</label>
          <select name="area_of_service" class="auto-submit">
            <option value="">Semua wilayah</option>
            <?php foreach ($areas_of_service as $aos): ?>
            <option value="<?= htmlspecialchars($aos) ?>" <?= $area_of_service === $aos ? 'selected' : '' ?>>
              <?= htmlspecialchars($aos) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Provinsi</label>
          <select name="province" id="sel-province" class="auto-submit">
            <option value="">Semua provinsi</option>
            <?php foreach ($provinces as $pv): ?>
            <option value="<?= htmlspecialchars($pv) ?>" <?= $province === $pv ? 'selected' : '' ?>>
              <?= htmlspecialchars($pv) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Kab/Kota</label>
          <select name="city" id="sel-city" class="auto-submit">
            <option value="">Semua kab/kota</option>
            <?php foreach ($cities as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
              <?= htmlspecialchars($c) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Kecamatan</label>
          <select name="district" class="auto-submit">
            <option value="">Semua kecamatan</option>
            <?php foreach ($districts as $dt): ?>
            <option value="<?= htmlspecialchars($dt) ?>" <?= $district === $dt ? 'selected' : '' ?>>
              <?= htmlspecialchars($dt) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i>Cari</button>
          <a href="data.php" class="btn"><i class="ti ti-x"></i>Reset</a>
        </div>
      </div>

      <!-- Baris 3: Sort jarak (compact) -->
      <div id="loc-bar" class="gps-sort-row">
        <span style="flex-shrink:0;color:var(--muted)">Sort:</span>
        <button type="button" id="btn-gps" class="btn" style="font-size:11px;padding:3px 8px;flex-shrink:0<?= $sort_dist ? ';color:#2563EB;border-color:#93c5fd' : '' ?>">
          <i class="ti ti-current-location<?= $sort_dist ? '-filled' : '' ?>"></i>
          <?= $sort_dist ? 'Aktif' : 'GPS' ?>
        </button>
        <input type="number" id="inp-lat" placeholder="Lat"
               value="<?= htmlspecialchars($_GET['ulat'] ?? '') ?>"
               step="0.000001" style="width:80px;font-size:13px;padding:3px 5px;border:1px solid var(--border);border-radius:6px;min-height:30px">
        <input type="number" id="inp-lng" placeholder="Lng"
               value="<?= htmlspecialchars($_GET['ulng'] ?? '') ?>"
               step="0.000001" style="width:80px;font-size:13px;padding:3px 5px;border:1px solid var(--border);border-radius:6px;min-height:30px">
        <button type="button" id="btn-apply-coord" class="btn" style="font-size:11px;padding:3px 8px;flex-shrink:0">
          <i class="ti ti-check"></i>
        </button>
        <?php if ($sort_dist): ?>
        <span style="font-size:10px;color:#2563EB;flex-shrink:0">
          <?= sprintf('%.4f', $user_lat) ?>, <?= sprintf('%.4f', $user_lng) ?>
        </span>
        <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query(array_diff_key($_GET, ['ulat'=>1,'ulng'=>1,'page'=>1]))) ?>"
           style="font-size:11px;color:#dc2626;text-decoration:none;flex-shrink:0">
          <i class="ti ti-x"></i>
        </a>
        <?php endif; ?>
      </div>

      </div><!-- .filter-body -->
    </div><!-- .filter-compact -->
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="ti ti-database-off"></i>
      Tidak ada data yang sesuai dengan filter yang dipilih.
    </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:28px">#</th>
          <th>Nama Klien</th>
          <th style="width:110px">Layanan</th>
          <th class="col-hide-sm" style="width:90px">Sub-layanan</th>
          <th>Nama Stasiun</th>
          <th class="col-hide-sm">Frekuensi</th>
          <th class="col-hide-md">No. Lisensi</th>
          <th class="col-hide-md">Berlaku s/d</th>
          <?php if ($sort_dist): ?><th><i class="ti ti-map-pin" style="font-size:12px"></i> Jarak</th><?php endif; ?>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $row): ?>
        <tr>
          <td class="td-muted"><?= $offset + $i + 1 ?></td>
          <td style="max-width:180px">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($row['clnt_name']) ?>">
              <?= htmlspecialchars($row['clnt_name']) ?>
            </div>
          </td>
          <td><?= svc_badge($row['service'] ?? '') ?></td>
          <td class="td-muted col-hide-sm"><?= htmlspecialchars($row['subservice'] ?? '-') ?></td>
          <td style="max-width:140px">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($row['stn_name'] ?? '') ?>">
              <?= htmlspecialchars($row['stn_name'] ?? '-') ?>
            </div>
          </td>
          <td class="td-mono col-hide-sm" style="white-space:nowrap">
            <?= $row['freq'] !== null ? number_format((float)$row['freq'], 3, ',', '.') . ' <span style="font-size:10px;color:var(--muted)">MHz</span>' : '<span class="td-muted">—</span>' ?>
          </td>
          <td class="td-mono col-hide-md"><?= htmlspecialchars($row['curr_lic_num'] ?? '-') ?></td>
          <td class="td-muted col-hide-md"><?= htmlspecialchars($row['validity_date'] ?? '-') ?></td>
          <?php if ($sort_dist): ?>
          <td style="white-space:nowrap;font-size:12px;color:#2563EB;font-weight:500">
            <?= $row['dist_km'] !== null ? fmt_dist((float)$row['dist_km']) : '—' ?>
          </td>
          <?php endif; ?>
          <td>
            <a href="detail.php?id=<?= $row['id'] ?>" class="btn" style="padding:4px 8px;font-size:11px">
              <i class="ti ti-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <div class="pag-info">
      Menampilkan <?= number_format($offset + 1, 0, ',', '.') ?>–<?= number_format(min($offset + $per_page, $total_rows), 0, ',', '.') ?>
      dari <?= number_format($total_rows, 0, ',', '.') ?> data
    </div>
    <div class="pag-links">
      <?php if ($page_num > 1): ?>
        <a href="<?= page_url(1) ?>"><i class="ti ti-chevrons-left" style="font-size:13px"></i></a>
        <a href="<?= page_url($page_num - 1) ?>"><i class="ti ti-chevron-left" style="font-size:13px"></i></a>
      <?php endif; ?>
      <?php
      $start = max(1, $page_num - 2);
      $end   = min($total_pages, $page_num + 2);
      if ($start > 1) echo '<span class="dots">…</span>';
      for ($p = $start; $p <= $end; $p++):
      ?>
        <?php if ($p === $page_num): ?>
          <span class="active"><?= $p ?></span>
        <?php else: ?>
          <a href="<?= page_url($p) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($end < $total_pages) echo '<span class="dots">…</span>'; ?>
      <?php if ($page_num < $total_pages): ?>
        <a href="<?= page_url($page_num + 1) ?>"><i class="ti ti-chevron-right" style="font-size:13px"></i></a>
        <a href="<?= page_url($total_pages) ?>"><i class="ti ti-chevrons-right" style="font-size:13px"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
(function () {

// ── GPS ───────────────────────────────────────────────────────
document.getElementById('btn-gps').addEventListener('click', function () {
  if (!navigator.geolocation) { alert('Browser tidak mendukung geolocation.'); return; }
  var btn = this;
  btn.innerHTML = '<i class="ti ti-loader-2"></i> Mencari...';
  btn.disabled = true;
  navigator.geolocation.getCurrentPosition(
    function (pos) {
      var lat = pos.coords.latitude;
      var lng = pos.coords.longitude;
      document.getElementById('f-ulat').value = lat;
      document.getElementById('f-ulng').value = lng;
      document.getElementById('inp-lat').value = lat.toFixed(6);
      document.getElementById('inp-lng').value = lng.toFixed(6);
      // Reset ke halaman 1 lalu submit
      var pgInput = document.querySelector('input[name="page"]');
      if (pgInput) pgInput.value = 1;
      document.getElementById('data-form').submit();
    },
    function (err) {
      var msg = {1:'Akses lokasi ditolak.',2:'Posisi tidak dapat ditentukan.',3:'Timeout.'}[err.code] || 'Gagal.';
      alert(msg);
      btn.innerHTML = '<i class="ti ti-current-location"></i> Lokasi saya (GPS)';
      btn.disabled = false;
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
  );
});

// ── Input koordinat manual ────────────────────────────────────
document.getElementById('btn-apply-coord').addEventListener('click', function () {
  var lat = parseFloat(document.getElementById('inp-lat').value);
  var lng = parseFloat(document.getElementById('inp-lng').value);
  if (isNaN(lat) || isNaN(lng)) { alert('Masukkan latitude dan longitude yang valid.'); return; }
  if (lat < -90 || lat > 90)    { alert('Latitude harus antara -90 dan 90.'); return; }
  if (lng < -180 || lng > 180)  { alert('Longitude harus antara -180 dan 180.'); return; }
  document.getElementById('f-ulat').value = lat;
  document.getElementById('f-ulng').value = lng;
  document.getElementById('data-form').submit();
});

// Enter di input koordinat juga apply
['inp-lat','inp-lng'].forEach(function(id) {
  document.getElementById(id).addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('btn-apply-coord').click();
  });
});

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

