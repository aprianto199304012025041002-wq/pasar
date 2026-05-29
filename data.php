<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Data ISR';

// ── Input sanitize ────────────────────────────────────────────
$q          = trim($_GET['q']          ?? '');
$service    = trim($_GET['service']    ?? '');
$subservice = trim($_GET['subservice'] ?? '');
$province   = trim($_GET['province']   ?? '');
$city       = trim($_GET['city']       ?? '');
$district   = trim($_GET['district']   ?? '');
$page_num   = max(1, intval($_GET['page'] ?? 1));
$per_page   = PER_PAGE;

// ── Dropdown options ──────────────────────────────────────────
$services    = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$provinces   = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);

// Sub-layanan: filter by service if selected
if ($service !== '') {
    $ss_stmt = $pdo->prepare("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL AND service = ? ORDER BY subservice");
    $ss_stmt->execute([$service]);
} else {
    $ss_stmt = $pdo->query("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL ORDER BY subservice");
}
$subservices = $ss_stmt->fetchAll(PDO::FETCH_COLUMN);

// Kabupaten: filter by province if selected
if ($province !== '') {
    $city_stmt = $pdo->prepare("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL AND province = ? ORDER BY city");
    $city_stmt->execute([$province]);
} else {
    $city_stmt = $pdo->query("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL ORDER BY city");
}
$cities = $city_stmt->fetchAll(PDO::FETCH_COLUMN);

// Kecamatan: filter by city if selected
if ($city !== '') {
    $dist_stmt = $pdo->prepare("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL AND city = ? ORDER BY district");
    $dist_stmt->execute([$city]);
} else {
    $dist_stmt = $pdo->query("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL ORDER BY district");
}
$districts = $dist_stmt->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE ───────────────────────────────────────────────
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = "(clnt_name LIKE ? OR curr_lic_num LIKE ?)";
    $like     = '%' . $q . '%';
    $params[] = $like; $params[] = $like;
}
if ($service    !== '') { $where[] = "service = ?";    $params[] = $service;    }
if ($subservice !== '') { $where[] = "subservice = ?"; $params[] = $subservice; }
if ($province   !== '') { $where[] = "province = ?";   $params[] = $province;   }
if ($city       !== '') { $where[] = "city = ?";       $params[] = $city;       }
if ($district   !== '') { $where[] = "district = ?";   $params[] = $district;   }

$clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Count ─────────────────────────────────────────────────────
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr $clause");
$count_stmt->execute($params);
$total_rows  = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$page_num    = min($page_num, $total_pages);
$offset      = ($page_num - 1) * $per_page;

// ── Data ──────────────────────────────────────────────────────
$data_stmt = $pdo->prepare(
    "SELECT id, clnt_name, service, subservice,
            curr_lic_num, stn_name, validity_date
     FROM isr $clause
     ORDER BY clnt_name ASC
     LIMIT $per_page OFFSET $offset"
);
$data_stmt->execute($params);
$rows = $data_stmt->fetchAll();

// ── Badge helper ──────────────────────────────────────────────
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

// ── Pagination URL builder ────────────────────────────────────
function page_url(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Data ISR
  <div class="page-sub">Total <?= number_format($total_rows, 0, ',', '.') ?> record ditemukan</div>
</div>

<div class="card">

  <!-- Filter + Search Bar -->
  <form method="get" action="">
    <div class="filter-grid">

      <div class="form-group filter-full">
        <label>Cari nama klien / No. ISR</label>
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input type="search" name="q" id="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Ketik nama klien atau nomor lisensi...">
        </div>
      </div>

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

      <div class="form-group filter-full">
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
        <label>Kabupaten/Kota</label>
        <select name="city" id="sel-city" class="auto-submit">
          <option value="">Semua kabupaten</option>
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

      <div class="filter-actions filter-full">
        <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i>Cari</button>
        <a href="data.php" class="btn"><i class="ti ti-x"></i>Reset</a>
      </div>

    </div>
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
          <th style="width:36px">#</th>
          <th>Nama Klien</th>
          <th>Layanan</th>
          <th>Sub-layanan</th>
          <th>Nama Stasiun</th>
          <th>No. Lisensi</th>
          <th>Berlaku s/d</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $row): ?>
        <tr>
          <td class="td-muted"><?= $offset + $i + 1 ?></td>
          <td style="max-width:200px">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($row['clnt_name']) ?>">
              <?= htmlspecialchars($row['clnt_name']) ?>
            </div>
          </td>
          <td><?= svc_badge($row['service'] ?? '') ?></td>
          <td class="td-muted"><?= htmlspecialchars($row['subservice'] ?? '-') ?></td>
          <td style="max-width:160px">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($row['stn_name'] ?? '') ?>">
              <?= htmlspecialchars($row['stn_name'] ?? '-') ?>
            </div>
          </td>
          <td class="td-mono"><?= htmlspecialchars($row['curr_lic_num'] ?? '-') ?></td>
          <td class="td-muted"><?= htmlspecialchars($row['validity_date'] ?? '-') ?></td>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>