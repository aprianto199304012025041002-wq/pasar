<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Data ISR';

// ── Input sanitize ────────────────────────────────────────────
$q        = trim($_GET['q']        ?? '');
$service  = trim($_GET['service']  ?? '');
$city     = trim($_GET['city']     ?? '');
$province = trim($_GET['province'] ?? '');
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = PER_PAGE;

// ── Dropdown options ──────────────────────────────────────────
$services  = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$cities    = $pdo->query("SELECT DISTINCT city    FROM isr WHERE city    IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$provinces = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE ───────────────────────────────────────────────
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = "(clnt_name LIKE ? OR curr_lic_num LIKE ? OR stn_name LIKE ? OR no_simf LIKE ?)";
    $like     = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($service !== '')  { $where[] = "service = ?";  $params[] = $service;  }
if ($city !== '')     { $where[] = "city = ?";     $params[] = $city;     }
if ($province !== '') { $where[] = "province = ?"; $params[] = $province; }

$clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Count ─────────────────────────────────────────────────────
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM isr $clause");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$page_num    = min($page_num, $total_pages);
$offset      = ($page_num - 1) * $per_page;

// ── Data ──────────────────────────────────────────────────────
$data_stmt = $pdo->prepare(
    "SELECT id, clnt_name, service, subservice, freq, freq_pair,
            curr_lic_num, stn_name, district, city, province,
            licence_date, validity_date, masa_laku, umur_isr,
            no_simf, trans_type, erp_pwr_dbm, sid_lat, sid_long
     FROM isr $clause
     ORDER BY clnt_name ASC, freq ASC
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
    <div style="padding:14px 18px;border-bottom:0.5px solid var(--border);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">

      <div class="form-group">
        <label>Cari nama / No. ISR</label>
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input type="search" name="q" id="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Ketik nama entitas atau nomor lisensi...">
        </div>
      </div>

      <div class="form-group">
        <label>Layanan</label>
        <select name="service" class="auto-submit">
          <option value="">Semua layanan</option>
          <?php foreach ($services as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= $service === $s ? 'selected' : '' ?>>
            <?= htmlspecialchars($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Kota/Kabupaten</label>
        <select name="city" class="auto-submit">
          <option value="">Semua kota</option>
          <?php foreach ($cities as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
            <?= htmlspecialchars($c) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Provinsi</label>
        <select name="province" class="auto-submit">
          <option value="">Semua provinsi</option>
          <?php foreach ($provinces as $pv): ?>
          <option value="<?= htmlspecialchars($pv) ?>" <?= $province === $pv ? 'selected' : '' ?>>
            <?= htmlspecialchars($pv) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;gap:6px;align-items:flex-end">
        <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i>Cari</button>
        <a href="data.php" class="btn"><i class="ti ti-x"></i>Reset</a>
      </div>

    </div>

    <!-- Filter pills (service shortcut) -->
    <div class="filter-bar">
      <label>Filter cepat:</label>
      <button type="button" class="filter-btn <?= $service === '' ? 'active' : '' ?>"
              data-filter="" data-group="svc" data-target="svc-hidden">Semua</button>
      <?php foreach ($services as $s): ?>
      <button type="button" class="filter-btn <?= $service === $s ? 'active' : '' ?>"
              data-filter="<?= htmlspecialchars($s) ?>" data-group="svc" data-target="svc-hidden">
        <?= htmlspecialchars($s) ?>
      </button>
      <?php endforeach; ?>
      <input type="hidden" id="svc-hidden" name="service_pill" value="">
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
          <th>Nama Entitas</th>
          <th>Layanan</th>
          <th>Sub-layanan</th>
          <th>Frekuensi (MHz)</th>
          <th>Nama Stasiun</th>
          <th>Kecamatan</th>
          <th>Kota/Kab</th>
          <th>No. Lisensi</th>
          <th>Berlaku s/d</th>
          <th>Masa Laku</th>
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
          <td class="td-freq"><?= $row['freq'] !== null ? number_format($row['freq'], 3, ',', '.') : '-' ?></td>
          <td style="max-width:160px">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($row['stn_name'] ?? '') ?>">
              <?= htmlspecialchars($row['stn_name'] ?? '-') ?>
            </div>
          </td>
          <td><?= htmlspecialchars($row['district'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['city'] ?? '-') ?></td>
          <td class="td-mono"><?= htmlspecialchars($row['curr_lic_num'] ?? '-') ?></td>
          <td class="td-muted"><?= htmlspecialchars($row['validity_date'] ?? '-') ?></td>
          <td class="td-muted"><?= htmlspecialchars($row['masa_laku'] ?? '-') ?></td>
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
