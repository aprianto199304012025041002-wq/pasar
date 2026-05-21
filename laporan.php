<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Laporan';

// ── Rekapitulasi per service × province ──────────────────────
$rekap = $pdo->query(
    "SELECT service, province, COUNT(*) AS cnt
     FROM isr
     GROUP BY service, province
     ORDER BY service, cnt DESC"
)->fetchAll();

// ── ISR per subservice ────────────────────────────────────────
$by_sub = $pdo->query(
    "SELECT subservice, COUNT(*) AS cnt FROM isr
     GROUP BY subservice ORDER BY cnt DESC"
)->fetchAll();

// ── ISR per kota ─────────────────────────────────────────────
$by_city = $pdo->query(
    "SELECT city, province, COUNT(*) AS cnt FROM isr
     WHERE city IS NOT NULL GROUP BY city, province ORDER BY cnt DESC"
)->fetchAll();

// ── ISR segera kadaluarsa (masa_laku 6 bulan ke depan) ───────
$expiring6 = $pdo->query(
    "SELECT clnt_name, curr_lic_num, service, city, masa_laku, validity_date
     FROM isr
     WHERE masa_laku IS NOT NULL
       AND STR_TO_DATE(masa_laku,'%d-%b-%y') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
     ORDER BY STR_TO_DATE(masa_laku,'%d-%b-%y') ASC
     LIMIT 50"
)->fetchAll();

// ── ISR per zona ──────────────────────────────────────────────
$by_zona = $pdo->query(
    "SELECT zona, COUNT(*) AS cnt FROM isr
     WHERE zona IS NOT NULL GROUP BY zona ORDER BY cnt DESC"
)->fetchAll();

$total = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <div class="page-title" style="margin-bottom:0">Laporan Rekapitulasi ISR</div>
    <div class="page-sub">Data per query 14 April 2026 · <?= number_format($total,0,',','.') ?> total ISR</div>
  </div>
  <button onclick="window.print()" class="btn"><i class="ti ti-printer"></i>Cetak</button>
</div>

<!-- Rekap per layanan & provinsi -->
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">Rekapitulasi per Layanan & Provinsi</span></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Layanan</th>
          <th>Provinsi</th>
          <th style="text-align:right">Jumlah ISR</th>
          <th style="text-align:right">% dari Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $svc_class = [
            'Fixed Service'         => 'badge-fixed',
            'Land Mobile (public)'  => 'badge-lm-pub',
            'Land Mobile (private)' => 'badge-lm-pri',
            'Broadcast'             => 'badge-broadcast',
            'Aeronautical'          => 'badge-aero',
            'Other Services'        => 'badge-other',
        ];
        $last_svc = null;
        foreach ($rekap as $r):
            $is_new = $r['service'] !== $last_svc;
            $last_svc = $r['service'];
            $cls = $svc_class[$r['service']] ?? 'badge-other';
        ?>
        <tr <?= $is_new ? 'style="border-top:2px solid var(--border)"' : '' ?>>
          <td><?= $is_new ? '<span class="badge '.$cls.'">'.htmlspecialchars($r['service']).'</span>' : '' ?></td>
          <td><?= htmlspecialchars($r['province'] ?? '—') ?></td>
          <td style="text-align:right;font-weight:500"><?= number_format($r['cnt'],0,',','.') ?></td>
          <td style="text-align:right;color:var(--muted)"><?= number_format($r['cnt']/$total*100, 2, ',', '.') ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

  <!-- Per sub-layanan -->
  <div class="card">
    <div class="card-header"><span class="card-title">ISR per Sub-layanan</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Sub-layanan</th><th style="text-align:right">Jumlah</th><th style="text-align:right">%</th></tr></thead>
        <tbody>
          <?php foreach ($by_sub as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['subservice'] ?? '—') ?></td>
            <td style="text-align:right;font-weight:500"><?= number_format($s['cnt'],0,',','.') ?></td>
            <td style="text-align:right;color:var(--muted)"><?= number_format($s['cnt']/$total*100,1,',','.') ?>%</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Per kota -->
  <div class="card">
    <div class="card-header"><span class="card-title">ISR per Kota/Kabupaten</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Kota/Kabupaten</th><th>Provinsi</th><th style="text-align:right">Jumlah</th></tr></thead>
        <tbody>
          <?php foreach ($by_city as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['city'] ?? '—') ?></td>
            <td class="td-muted"><?= htmlspecialchars($c['province'] ?? '') ?></td>
            <td style="text-align:right;font-weight:500"><?= number_format($c['cnt'],0,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ISR segera kadaluarsa -->
<?php if (!empty($expiring6)): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <span class="card-title" style="color:var(--red)">
      <i class="ti ti-alert-triangle" style="font-size:15px;margin-right:6px"></i>
      ISR Segera Kadaluarsa (≤ 6 bulan)
    </span>
    <span class="badge badge-broadcast"><?= count($expiring6) ?> ISR</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama Entitas</th>
          <th>No. Lisensi</th>
          <th>Layanan</th>
          <th>Kota</th>
          <th>Masa Laku</th>
          <th>Berlaku s/d</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($expiring6 as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['clnt_name']) ?></td>
          <td class="td-mono"><?= htmlspecialchars($e['curr_lic_num'] ?? '—') ?></td>
          <td><?= htmlspecialchars($e['service'] ?? '—') ?></td>
          <td><?= htmlspecialchars($e['city'] ?? '—') ?></td>
          <td style="color:var(--red);font-weight:500"><?= htmlspecialchars($e['masa_laku']) ?></td>
          <td><?= htmlspecialchars($e['validity_date'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Per zona -->
<div class="card">
  <div class="card-header"><span class="card-title">ISR per Zona Frekuensi</span></div>
  <div class="card-body">
    <div class="bar-list">
      <?php
      $max_z = $by_zona[0]['cnt'] ?? 1;
      foreach ($by_zona as $z): ?>
      <div class="bar-item">
        <div class="bar-label"><?= htmlspecialchars($z['zona'] ?? '—') ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= round($z['cnt']/$max_z*100) ?>%;background:#7F77DD"></div></div>
        <div class="bar-val"><?= number_format($z['cnt'],0,',','.') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
@media print {
  .app-header, .app-footer, .btn, nav { display: none !important; }
  .card { break-inside: avoid; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
