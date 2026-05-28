<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Dashboard';

// ── Tanggal query ─────────────────────────────────────────────
$tgl_query_raw = $pdo->query("SELECT MAX(TGL_QUERY) FROM isr")->fetchColumn();
$tgl_query = $tgl_query_raw ? (new DateTime($tgl_query_raw))->format('d M Y') : '—';

// ── Statistik utama ───────────────────────────────────────────
$total      = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();
$n_klien    = $pdo->query("SELECT COUNT(DISTINCT clnt_name) FROM isr WHERE clnt_name IS NOT NULL AND clnt_name != ''")->fetchColumn();

// ── Per service + subservice ──────────────────────────────────
$by_svc = $pdo->query("SELECT service, COUNT(*) AS cnt FROM isr GROUP BY service ORDER BY cnt DESC")->fetchAll();

// Group subservice per service
$sub_raw = $pdo->query(
    "SELECT service, subservice, COUNT(*) AS cnt FROM isr
     WHERE subservice IS NOT NULL AND subservice != ''
     GROUP BY service, subservice ORDER BY service, cnt DESC"
)->fetchAll();
$sub_by_svc = [];
foreach ($sub_raw as $r) {
    $sub_by_svc[$r['service']][] = $r;
}

// ── Wilayah: provinsi → kab → kecamatan ──────────────────────
$by_prov = $pdo->query(
    "SELECT province, COUNT(*) AS cnt FROM isr
     WHERE province IS NOT NULL AND province != ''
     GROUP BY province ORDER BY cnt DESC"
)->fetchAll();

$by_city = $pdo->query(
    "SELECT province, city, COUNT(*) AS cnt FROM isr
     WHERE city IS NOT NULL AND city != ''
     GROUP BY province, city ORDER BY province, cnt DESC"
)->fetchAll();
$city_by_prov = [];
foreach ($by_city as $r) {
    $city_by_prov[$r['province']][] = $r;
}

$by_dist = $pdo->query(
    "SELECT province, city, district, COUNT(*) AS cnt FROM isr
     WHERE district IS NOT NULL AND district != ''
     GROUP BY province, city, district ORDER BY city, cnt DESC"
)->fetchAll();
$dist_by_city = [];
foreach ($by_dist as $r) {
    $dist_by_city[$r['city']][] = $r;
}

// ── Semua klien ───────────────────────────────────────────────
$by_clnt = $pdo->query(
    "SELECT clnt_name, COUNT(*) AS cnt FROM isr
     WHERE clnt_name IS NOT NULL AND clnt_name != ''
     GROUP BY clnt_name ORDER BY cnt DESC"
)->fetchAll();
$max_c = !empty($by_clnt) ? $by_clnt[0]['cnt'] : 1;

// ── Helpers ───────────────────────────────────────────────────
$svc_map = [
    'Fixed Service'         => ['color' => '#185FA5', 'label' => 'Fixed Service'],
    'Land Mobile (public)'  => ['color' => '#EF9F27', 'label' => 'LM Public'],
    'Land Mobile (private)' => ['color' => '#7F77DD', 'label' => 'LM Private'],
    'Broadcast'             => ['color' => '#E24B4A', 'label' => 'Broadcast'],
    'Aeronautical'          => ['color' => '#1D9E75', 'label' => 'Aeronautical'],
    'Other Services'        => ['color' => '#888780', 'label' => 'Lainnya'],
];
$max_svc  = !empty($by_svc)  ? $by_svc[0]['cnt']  : 1;
$max_prov = !empty($by_prov) ? $by_prov[0]['cnt']  : 1;

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Dashboard
  <div class="page-sub">Ringkasan data ISR</div>
</div>

<!-- ── Panel 1: 3 stat ───────────────────────────────────────── -->
<div class="dash-stat3">
  <div class="stat-card blue">
    <div class="stat-label">Total ISR</div>
    <div class="stat-val"><?= number_format($total, 0, ',', '.') ?></div>
    <div class="stat-sub">seluruh layanan</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Klien</div>
    <div class="stat-val"><?= number_format($n_klien, 0, ',', '.') ?></div>
    <div class="stat-sub">entitas unik</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Tanggal Query</div>
    <div class="stat-val" style="font-size:18px"><?= htmlspecialchars($tgl_query) ?></div>
    <div class="stat-sub">data diambil</div>
  </div>
</div>

<!-- ── Panel 2: Wilayah expandable ───────────────────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">Sebaran wilayah</span>
    <span class="card-hint">klik untuk expand</span>
  </div>
  <div class="dash-barlist">
    <?php foreach ($by_prov as $pi => $prov):
      $wp = $max_prov > 0 ? round($prov['cnt'] / $max_prov * 100) : 0;
      $pct_p = $total > 0 ? round($prov['cnt'] / $total * 100, 1) : 0;
      $kabs  = $city_by_prov[$prov['province']] ?? [];
      $max_kab = !empty($kabs) ? $kabs[0]['cnt'] : 1;
      $pid = 'prov_' . $pi;
    ?>
    <div class="expand-row" onclick="toggleExpand('<?= $pid ?>')">
      <div class="er-label">
        <i class="ti ti-chevron-right er-chev" id="chev_<?= $pid ?>"></i>
        <?= htmlspecialchars($prov['province']) ?>
      </div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $wp ?>%;background:#185FA5"></div></div>
      <div class="er-val"><?= number_format($prov['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_p ?>%</span></div>
    </div>
    <div class="expand-children" id="<?= $pid ?>">
      <?php foreach ($kabs as $ki => $kab):
        $wk = $max_kab > 0 ? round($kab['cnt'] / $max_kab * 100) : 0;
        $pct_k = $total > 0 ? round($kab['cnt'] / $total * 100, 1) : 0;
        $kecs  = $dist_by_city[$kab['city']] ?? [];
        $max_kec = !empty($kecs) ? $kecs[0]['cnt'] : 1;
        $kid = 'kab_' . $pi . '_' . $ki;
      ?>
      <div class="expand-row indent1" onclick="toggleExpand('<?= $kid ?>');event.stopPropagation()">
        <div class="er-label">
          <i class="ti ti-chevron-right er-chev" id="chev_<?= $kid ?>"></i>
          <?= htmlspecialchars($kab['city']) ?>
        </div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $wk ?>%;background:#7F77DD"></div></div>
        <div class="er-val"><?= number_format($kab['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_k ?>%</span></div>
      </div>
      <div class="expand-children" id="<?= $kid ?>">
        <?php foreach ($kecs as $kec):
          $wkc = $max_kec > 0 ? round($kec['cnt'] / $max_kec * 100) : 0;
          $pct_kc = $total > 0 ? round($kec['cnt'] / $total * 100, 1) : 0;
        ?>
        <div class="expand-row indent2">
          <div class="er-label"><?= htmlspecialchars($kec['district']) ?></div>
          <div class="bar-track"><div class="bar-fill" style="width:<?= $wkc ?>%;background:#1D9E75;opacity:.7"></div></div>
          <div class="er-val"><?= number_format($kec['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_kc ?>%</span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Panel 3: Service + Subservice expandable ───────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">ISR per Service & Subservice</span>
    <span class="card-hint">klik untuk expand</span>
  </div>
  <div class="dash-barlist">
    <?php foreach ($by_svc as $si => $svc):
      $meta  = $svc_map[$svc['service']] ?? ['color'=>'#888780','label'=>$svc['service']];
      $ws    = $max_svc > 0 ? round($svc['cnt'] / $max_svc * 100) : 0;
      $pct_s = $total > 0 ? round($svc['cnt'] / $total * 100, 1) : 0;
      $subs  = $sub_by_svc[$svc['service']] ?? [];
      $max_sub = !empty($subs) ? $subs[0]['cnt'] : 1;
      $sid = 'svc_' . $si;
    ?>
    <div class="expand-row" onclick="toggleExpand('<?= $sid ?>')">
      <div class="er-label">
        <i class="ti ti-chevron-right er-chev" id="chev_<?= $sid ?>"></i>
        <?= htmlspecialchars($meta['label']) ?>
      </div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $ws ?>%;background:<?= $meta['color'] ?>"></div></div>
      <div class="er-val"><?= number_format($svc['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_s ?>%</span></div>
    </div>
    <div class="expand-children" id="<?= $sid ?>">
      <?php foreach ($subs as $sub):
        $wsb   = $max_sub > 0 ? round($sub['cnt'] / $max_sub * 100) : 0;
        $pct_b = $total > 0 ? round($sub['cnt'] / $total * 100, 1) : 0;
      ?>
      <div class="expand-row indent1">
        <div class="er-label"><?= htmlspecialchars($sub['subservice']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $wsb ?>%;background:<?= $meta['color'] ?>;opacity:.55"></div></div>
        <div class="er-val"><?= number_format($sub['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_b ?>%</span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Panel 4: Semua klien ──────────────────────────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">Semua klien</span>
    <span class="card-hint"><?= number_format($n_klien, 0, ',', '.') ?> entitas</span>
  </div>
  <div style="padding:10px 14px 4px">
    <input type="search" id="clientSearch" placeholder="Cari nama klien..."
           class="dash-search" oninput="filterClients()">
  </div>
  <div class="dash-barlist dash-scroll" id="clientList">
    <?php foreach ($by_clnt as $c):
      $wc = $max_c > 0 ? round($c['cnt'] / $max_c * 100) : 0;
      $pct_c = $total > 0 ? round($c['cnt'] / $total * 100, 1) : 0;
    ?>
    <div class="expand-row client-row" data-name="<?= htmlspecialchars(strtolower($c['clnt_name'])) ?>">
      <div class="er-label"><?= htmlspecialchars($c['clnt_name']) ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $wc ?>%;background:#1D9E75"></div></div>
      <div class="er-val"><?= number_format($c['cnt'], 0, ',', '.') ?> <span class="bar-pct"><?= $pct_c ?>%</span></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function toggleExpand(id) {
  var el   = document.getElementById(id);
  var chev = document.getElementById('chev_' + id);
  if (!el) return;
  var open = el.classList.toggle('open');
  if (chev) chev.classList.toggle('open', open);
}

function filterClients() {
  var q = document.getElementById('clientSearch').value.toLowerCase();
  document.querySelectorAll('#clientList .client-row').forEach(function(row) {
    row.style.display = row.dataset.name.includes(q) ? '' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>