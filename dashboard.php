<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Dashboard';

// ── Tanggal query (dinamis) ────────────────────────────────────
$tgl_query_raw = $pdo->query("SELECT MAX(TGL_QUERY) FROM isr")->fetchColumn();
$tgl_query = $tgl_query_raw
    ? (new DateTime($tgl_query_raw))->format('d F Y')
    : '—';

// ── Statistik utama ───────────────────────────────────────────
$total   = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();
$n_prov  = $pdo->query("SELECT COUNT(DISTINCT province) FROM isr WHERE province IS NOT NULL AND province != ''")->fetchColumn();
$n_city  = $pdo->query("SELECT COUNT(DISTINCT city)     FROM isr WHERE city     IS NOT NULL AND city     != ''")->fetchColumn();
$n_dist  = $pdo->query("SELECT COUNT(DISTINCT district) FROM isr WHERE district IS NOT NULL AND district != ''")->fetchColumn();

// Per service
$by_svc  = $pdo->query("SELECT service, COUNT(*) AS cnt FROM isr GROUP BY service ORDER BY cnt DESC")->fetchAll();

// Per subservice
$by_sub  = $pdo->query(
    "SELECT subservice, service, COUNT(*) AS cnt FROM isr
     WHERE subservice IS NOT NULL AND subservice != ''
     GROUP BY subservice, service ORDER BY cnt DESC"
)->fetchAll();

// Per provinsi
$by_prov = $pdo->query("SELECT province, COUNT(*) AS cnt FROM isr GROUP BY province ORDER BY cnt DESC")->fetchAll();

// Top 10 kecamatan
$by_dist = $pdo->query(
    "SELECT district, city, COUNT(*) AS cnt FROM isr
     WHERE district IS NOT NULL AND district != ''
     GROUP BY district, city ORDER BY cnt DESC LIMIT 10"
)->fetchAll();

// Top 10 entitas
$by_clnt = $pdo->query(
    "SELECT clnt_name, COUNT(*) AS cnt FROM isr
     GROUP BY clnt_name ORDER BY cnt DESC LIMIT 10"
)->fetchAll();

// ISR segera habis
$expiring = $pdo->query(
    "SELECT COUNT(*) FROM isr WHERE masa_laku IS NOT NULL
     AND STR_TO_DATE(masa_laku,'%d-%b-%y') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR)"
)->fetchColumn();

$svc_map = [
    'Fixed Service'         => ['color' => '#185FA5', 'label' => 'Fixed Service'],
    'Land Mobile (public)'  => ['color' => '#EF9F27', 'label' => 'LM Public'],
    'Land Mobile (private)' => ['color' => '#7F77DD', 'label' => 'LM Private'],
    'Broadcast'             => ['color' => '#E24B4A', 'label' => 'Broadcast'],
    'Aeronautical'          => ['color' => '#1D9E75', 'label' => 'Aeronautical'],
    'Other Services'        => ['color' => '#888780', 'label' => 'Lainnya'],
];

$max_svc = !empty($by_svc) ? $by_svc[0]['cnt'] : 1;
$max_sub = !empty($by_sub) ? $by_sub[0]['cnt'] : 1;
$max_d   = !empty($by_dist) ? $by_dist[0]['cnt'] : 1;
$max_c   = !empty($by_clnt) ? $by_clnt[0]['cnt'] : 1;

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Dashboard
  <div class="page-sub">Ringkasan data ISR per query <?= htmlspecialchars($tgl_query) ?></div>
</div>

<!-- ── Panel 1: Hero stats ───────────────────────────────────── -->
<div class="stat-grid-hero">
  <div class="stat-card blue">
    <div class="stat-label">Total ISR</div>
    <div class="stat-val"><?= number_format($total, 0, ',', '.') ?></div>
    <div class="stat-sub">seluruh layanan</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Provinsi</div>
    <div class="stat-val"><?= (int)$n_prov ?></div>
    <div class="stat-sub">wilayah</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Kab / Kota</div>
    <div class="stat-val"><?= (int)$n_city ?></div>
    <div class="stat-sub">wilayah</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Kecamatan</div>
    <div class="stat-val"><?= (int)$n_dist ?></div>
    <div class="stat-sub">wilayah</div>
  </div>
</div>

<!-- ── Panel 2+3: Service & Subservice (2 kolom di desktop) ──── -->
<div class="svc-grid">

  <div class="card">
    <div class="card-header"><span class="card-title">ISR per Service</span></div>
    <div class="card-body">
      <div class="bar-list">
        <?php foreach ($by_svc as $s):
          $meta  = $svc_map[$s['service']] ?? ['color'=>'#888780','label'=>$s['service']];
          $pct   = $total > 0 ? round($s['cnt'] / $total * 100, 1) : 0;
          $width = $max_svc > 0 ? round($s['cnt'] / $max_svc * 100) : 0;
        ?>
        <div class="bar-item">
          <div class="bar-label"><?= htmlspecialchars($meta['label']) ?></div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $width ?>%;background:<?= $meta['color'] ?>"></div>
          </div>
          <div class="bar-val">
            <?= number_format($s['cnt'], 0, ',', '.') ?>
            <span class="bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">ISR per Subservice</span></div>
    <div class="card-body">
      <div class="bar-list">
        <?php foreach ($by_sub as $s):
          $meta  = $svc_map[$s['service']] ?? ['color'=>'#888780','label'=>$s['service']];
          $pct   = $total > 0 ? round($s['cnt'] / $total * 100, 1) : 0;
          $width = $max_sub > 0 ? round($s['cnt'] / $max_sub * 100) : 0;
        ?>
        <div class="bar-item">
          <div class="bar-label">
            <span class="sub-dot" style="background:<?= $meta['color'] ?>" title="<?= htmlspecialchars($meta['label']) ?>"></span><?= htmlspecialchars($s['subservice']) ?>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $width ?>%;background:<?= $meta['color'] ?>"></div>
          </div>
          <div class="bar-val">
            <?= number_format($s['cnt'], 0, ',', '.') ?>
            <span class="bar-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── Panel 4: Kecamatan, Entitas, Provinsi, Donut ─────────── -->
<div class="dash-grid" style="margin-top:12px">

  <div class="card">
    <div class="card-header"><span class="card-title">ISR per kecamatan (top 10)</span></div>
    <div class="card-body">
      <div class="bar-list">
        <?php foreach ($by_dist as $d): ?>
        <div class="bar-item">
          <div class="bar-label" title="<?= htmlspecialchars($d['district']) ?> — <?= htmlspecialchars($d['city']) ?>">
            <?= htmlspecialchars($d['district']) ?>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= round($d['cnt']/$max_d*100) ?>%"></div>
          </div>
          <div class="bar-val"><?= number_format($d['cnt'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Entitas terbanyak (top 10)</span></div>
    <div class="card-body">
      <div class="bar-list">
        <?php foreach ($by_clnt as $c): ?>
        <div class="bar-item">
          <div class="bar-label" title="<?= htmlspecialchars($c['clnt_name']) ?>">
            <?= htmlspecialchars($c['clnt_name']) ?>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= round($c['cnt']/$max_c*100) ?>%;background:#1D9E75"></div>
          </div>
          <div class="bar-val"><?= number_format($c['cnt'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Sebaran provinsi</span></div>
    <div class="card-body">
      <?php foreach ($by_prov as $p): ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
          <span><?= htmlspecialchars($p['province']) ?></span>
          <span style="font-weight:600"><?= number_format($p['cnt'],0,',','.') ?>
            <span style="color:var(--muted);font-weight:400;font-size:10.5px"> (<?= round($p['cnt']/$total*100,1) ?>%)</span>
          </span>
        </div>
        <div class="bar-track" style="height:8px">
          <div class="bar-fill" style="width:<?= round($p['cnt']/$total*100) ?>%;background:#7F77DD"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Komposisi layanan</span></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:center">
      <canvas id="pieChart" style="max-height:200px;max-width:100%"></canvas>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function() {
  var labels = <?= json_encode(array_map(fn($s) => $svc_map[$s['service']]['label'] ?? $s['service'], $by_svc)) ?>;
  var values = <?= json_encode(array_column($by_svc, 'cnt')) ?>;
  var colors = ['#185FA5','#EF9F27','#7F77DD','#E24B4A','#1D9E75','#888780'];
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8, boxWidth: 10 } },
        tooltip: { callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.parsed.toLocaleString('id-ID'); } } }
      }
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>