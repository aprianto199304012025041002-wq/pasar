<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Dashboard';

// ── Statistik utama ───────────────────────────────────────────
$total   = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();
$by_svc  = $pdo->query("SELECT service, COUNT(*) AS cnt FROM isr GROUP BY service ORDER BY cnt DESC")->fetchAll();
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

// ISR segera habis (masa_laku < 1 tahun ke depan)
$expiring = $pdo->query(
    "SELECT COUNT(*) FROM isr WHERE masa_laku IS NOT NULL
     AND STR_TO_DATE(masa_laku,'%d-%b-%y') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR)"
)->fetchColumn();

// Map service → badge class
$svc_map = [
    'Fixed Service'         => ['class' => 'badge-fixed',     'label' => 'Fixed Service'],
    'Land Mobile (public)'  => ['class' => 'badge-lm-pub',    'label' => 'LM Public'],
    'Land Mobile (private)' => ['class' => 'badge-lm-pri',    'label' => 'LM Private'],
    'Broadcast'             => ['class' => 'badge-broadcast',  'label' => 'Broadcast'],
    'Aeronautical'          => ['class' => 'badge-aero',       'label' => 'Aeronautical'],
    'Other Services'        => ['class' => 'badge-other',      'label' => 'Lainnya'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Dashboard
  <div class="page-sub">Ringkasan data ISR per query 14 April 2026</div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card blue">
    <div class="stat-label">Total ISR</div>
    <div class="stat-val"><?= number_format($total, 0, ',', '.') ?></div>
    <div class="stat-sub">seluruh layanan</div>
  </div>
  <?php foreach ($by_svc as $s):
    $meta = $svc_map[$s['service']] ?? ['class'=>'badge-other','label'=>$s['service']]; ?>
  <div class="stat-card">
    <div class="stat-label"><?= htmlspecialchars($meta['label']) ?></div>
    <div class="stat-val"><?= number_format($s['cnt'], 0, ',', '.') ?></div>
    <div class="stat-sub"><?= round($s['cnt']/$total*100, 1) ?>% dari total</div>
  </div>
  <?php endforeach; ?>
  <div class="stat-card red">
    <div class="stat-label">Segera Habis</div>
    <div class="stat-val"><?= number_format($expiring, 0, ',', '.') ?></div>
    <div class="stat-sub">< 1 tahun ke depan</div>
  </div>
</div>

<!-- Charts grid -->
<div class="dash-grid">

  <!-- Bar chart: top kecamatan -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">ISR per kecamatan (top 10)</span>
    </div>
    <div class="card-body">
      <div class="bar-list">
        <?php
        $max_d = $by_dist[0]['cnt'] ?? 1;
        foreach ($by_dist as $d): ?>
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

  <!-- Bar chart: top entitas -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Entitas terbanyak (top 10)</span>
    </div>
    <div class="card-body">
      <div class="bar-list">
        <?php
        $max_c = $by_clnt[0]['cnt'] ?? 1;
        foreach ($by_clnt as $c): ?>
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

  <!-- Donut-like: Provinsi -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Sebaran provinsi</span>
    </div>
    <div class="card-body">
      <?php foreach ($by_prov as $p): ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
          <span><?= htmlspecialchars($p['province']) ?></span>
          <span style="font-weight:500"><?= number_format($p['cnt'],0,',','.') ?>
            <span style="color:var(--muted);font-weight:400;font-size:11px">
              (<?= round($p['cnt']/$total*100,1) ?>%)
            </span>
          </span>
        </div>
        <div class="bar-track" style="height:10px">
          <div class="bar-fill" style="width:<?= round($p['cnt']/$total*100) ?>%;background:#7F77DD"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Komposisi layanan (pie placeholder via CSS) -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Komposisi layanan</span>
    </div>
    <div class="card-body">
      <canvas id="pieChart" style="max-height:220px"></canvas>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function() {
  var labels = <?= json_encode(array_column($by_svc, 'service')) ?>;
  var values = <?= json_encode(array_column($by_svc, 'cnt')) ?>;
  var colors = ['#185FA5','#EF9F27','#7F77DD','#E24B4A','#1D9E75','#888780'];
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } },
        tooltip: { callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.parsed.toLocaleString('id-ID'); } } }
      }
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
