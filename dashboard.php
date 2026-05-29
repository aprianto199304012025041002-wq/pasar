<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Dashboard';

// ── Tanggal query ─────────────────────────────────────────────
$tgl_query_raw = $pdo->query("SELECT MAX(TGL_QUERY) FROM isr")->fetchColumn();
$tgl_query = $tgl_query_raw ? (new DateTime($tgl_query_raw))->format('d M Y') : '—';

// ── Statistik utama ───────────────────────────────────────────
$total   = $pdo->query("SELECT COUNT(*) FROM isr")->fetchColumn();
$n_klien = $pdo->query("SELECT COUNT(DISTINCT clnt_name) FROM isr WHERE clnt_name IS NOT NULL AND clnt_name != ''")->fetchColumn();
$n_prov  = $pdo->query("SELECT COUNT(DISTINCT province) FROM isr WHERE province IS NOT NULL AND province != ''")->fetchColumn();
$n_city  = $pdo->query("SELECT COUNT(DISTINCT city) FROM isr WHERE city IS NOT NULL AND city != ''")->fetchColumn();
$n_dist  = $pdo->query("SELECT COUNT(DISTINCT district) FROM isr WHERE district IS NOT NULL AND district != ''")->fetchColumn();

// ── Per service + subservice ──────────────────────────────────
$by_svc = $pdo->query("SELECT service, COUNT(*) AS cnt FROM isr GROUP BY service ORDER BY cnt DESC")->fetchAll();
$sub_raw = $pdo->query(
    "SELECT service, subservice, COUNT(*) AS cnt FROM isr
     WHERE subservice IS NOT NULL AND subservice != ''
     GROUP BY service, subservice ORDER BY service, cnt DESC"
)->fetchAll();
$sub_by_svc = [];
foreach ($sub_raw as $r) $sub_by_svc[$r['service']][] = $r;

// ── Wilayah ───────────────────────────────────────────────────
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
foreach ($by_city as $r) $city_by_prov[$r['province']][] = $r;

$by_dist = $pdo->query(
    "SELECT city, district, COUNT(*) AS cnt FROM isr
     WHERE district IS NOT NULL AND district != ''
     GROUP BY city, district ORDER BY city, cnt DESC"
)->fetchAll();
$dist_by_city = [];
foreach ($by_dist as $r) $dist_by_city[$r['city']][] = $r;

// ── Semua klien + breakdown service/subservice ────────────────
$by_clnt = $pdo->query(
    "SELECT clnt_name, COUNT(*) AS cnt FROM isr
     WHERE clnt_name IS NOT NULL AND clnt_name != ''
     GROUP BY clnt_name ORDER BY cnt DESC"
)->fetchAll();

$clnt_svc_raw = $pdo->query(
    "SELECT clnt_name, service, subservice, COUNT(*) AS cnt FROM isr
     WHERE clnt_name IS NOT NULL AND clnt_name != ''
     GROUP BY clnt_name, service, subservice ORDER BY clnt_name, cnt DESC"
)->fetchAll();
$clnt_svc = [];
foreach ($clnt_svc_raw as $r) $clnt_svc[$r['clnt_name']][] = $r;

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
$max_clnt = !empty($by_clnt) ? $by_clnt[0]['cnt']  : 1;

// encode all client data as JSON for JS filter
$clnt_json = [];
foreach ($by_clnt as $c) {
    $subs = [];
    foreach ($clnt_svc[$c['clnt_name']] ?? [] as $s) {
        $meta = $svc_map[$s['service']] ?? ['color'=>'#888780','label'=>$s['service']];
        $subs[] = [
            'svc'   => $meta['label'],
            'col'   => $meta['color'],
            'sub'   => $s['subservice'] ?? '—',
            'c'     => (int)$s['cnt'],
            'p'     => $total > 0 ? round($s['cnt']/$total*100,1) : 0,
        ];
    }
    $clnt_json[] = [
        'n'    => $c['clnt_name'],
        'c'    => (int)$c['cnt'],
        'p'    => $total > 0 ? round($c['cnt']/$total*100,1) : 0,
        'w'    => $max_clnt > 0 ? round($c['cnt']/$max_clnt*100) : 0,
        'subs' => $subs,
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">Dashboard
  <div class="page-sub">Ringkasan data ISR</div>
</div>

<!-- ── Panel 1: 3 stat ───────────────────────────────────────── -->
<div class="dash-stat3">
  <div class="stat-card">
    <div class="stat-label">Total ISR</div>
    <div class="stat-val"><?= number_format($total,0,',','.') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Klien</div>
    <div class="stat-val"><?= number_format($n_klien,0,',','.') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Data per Tanggal</div>
    <div class="stat-val" style="font-size:18px"><?= htmlspecialchars($tgl_query) ?></div>
  </div>
</div>

<!-- ── Panel 2: 3 geo stat ───────────────────────────────────── -->
<div class="dash-stat3">
  <div class="stat-card">
    <div class="stat-label">Provinsi</div>
    <div class="stat-val"><?= (int)$n_prov ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Kab / Kota</div>
    <div class="stat-val"><?= (int)$n_city ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Kecamatan</div>
    <div class="stat-val"><?= (int)$n_dist ?></div>
  </div>
</div>

<!-- ── Panel 3: Wilayah expandable ───────────────────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">Sebaran ISR per wilayah kerja</span>
    <span class="card-hint">klik untuk expand</span>
  </div>
  <div class="dash-barlist">
    <?php foreach ($by_prov as $pi => $prov):
      $wp    = $max_prov > 0 ? round($prov['cnt']/$max_prov*100) : 0;
      $pct_p = $total > 0 ? round($prov['cnt']/$total*100,1) : 0;
      $kabs  = $city_by_prov[$prov['province']] ?? [];
      $max_k = !empty($kabs) ? $kabs[0]['cnt'] : 1;
      $pid   = 'p'.$pi;
    ?>
    <div class="expand-row" onclick="tog('<?= $pid ?>')">
      <div class="er-label"><i class="ti ti-chevron-right er-chev" id="ch_<?= $pid ?>"></i><?= htmlspecialchars($prov['province']) ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $wp ?>%;background:#185FA5"></div></div>
      <div class="er-val"><span class="er-abs"><?= number_format($prov['cnt'],0,',','.') ?></span><span class="er-pct"><?= $pct_p ?>%</span></div>
    </div>
    <div class="expand-children" id="<?= $pid ?>">
      <?php foreach ($kabs as $ki => $kab):
        $wk    = $max_k > 0 ? round($kab['cnt']/$max_k*100) : 0;
        $pct_k = $total > 0 ? round($kab['cnt']/$total*100,1) : 0;
        $kecs  = $dist_by_city[$kab['city']] ?? [];
        $max_kc = !empty($kecs) ? $kecs[0]['cnt'] : 1;
        $kid   = 'p'.$pi.'k'.$ki;
      ?>
      <div class="expand-row" onclick="tog('<?= $kid ?>');event.stopPropagation()">
        <div class="er-label indent1"><i class="ti ti-chevron-right er-chev" id="ch_<?= $kid ?>"></i><?= htmlspecialchars($kab['city']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $wk ?>%;background:#7F77DD"></div></div>
        <div class="er-val"><span class="er-abs"><?= number_format($kab['cnt'],0,',','.') ?></span><span class="er-pct"><?= $pct_k ?>%</span></div>
      </div>
      <div class="expand-children" id="<?= $kid ?>">
        <?php foreach ($kecs as $kec):
          $wkc   = $max_kc > 0 ? round($kec['cnt']/$max_kc*100) : 0;
          $pct_kc = $total > 0 ? round($kec['cnt']/$total*100,1) : 0;
        ?>
        <div class="expand-row">
          <div class="er-label indent2"><?= htmlspecialchars($kec['district']) ?></div>
          <div class="bar-track"><div class="bar-fill" style="width:<?= $wkc ?>%;background:#1D9E75;opacity:.7"></div></div>
          <div class="er-val"><span class="er-abs"><?= number_format($kec['cnt'],0,',','.') ?></span><span class="er-pct"><?= $pct_kc ?>%</span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Panel 4: Service & Subservice ─────────────────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">Sebaran ISR per Service dan Subservice</span>
    <span class="card-hint">klik untuk expand</span>
  </div>
  <div class="dash-barlist">
    <?php foreach ($by_svc as $si => $svc):
      $meta  = $svc_map[$svc['service']] ?? ['color'=>'#888780','label'=>$svc['service']];
      $ws    = $max_svc > 0 ? round($svc['cnt']/$max_svc*100) : 0;
      $pct_s = $total > 0 ? round($svc['cnt']/$total*100,1) : 0;
      $subs  = $sub_by_svc[$svc['service']] ?? [];
      $max_sb = !empty($subs) ? $subs[0]['cnt'] : 1;
      $sid   = 's'.$si;
    ?>
    <div class="expand-row" onclick="tog('<?= $sid ?>')">
      <div class="er-label"><i class="ti ti-chevron-right er-chev" id="ch_<?= $sid ?>"></i><?= htmlspecialchars($meta['label']) ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $ws ?>%;background:<?= $meta['color'] ?>"></div></div>
      <div class="er-val"><span class="er-abs"><?= number_format($svc['cnt'],0,',','.') ?></span><span class="er-pct"><?= $pct_s ?>%</span></div>
    </div>
    <div class="expand-children" id="<?= $sid ?>">
      <?php foreach ($subs as $sub):
        $wsb   = $max_sb > 0 ? round($sub['cnt']/$max_sb*100) : 0;
        $pct_b = $total > 0 ? round($sub['cnt']/$total*100,1) : 0;
      ?>
      <div class="expand-row">
        <div class="er-label indent1"><?= htmlspecialchars($sub['subservice']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $wsb ?>%;background:<?= $meta['color'] ?>;opacity:.55"></div></div>
        <div class="er-val"><span class="er-abs"><?= number_format($sub['cnt'],0,',','.') ?></span><span class="er-pct"><?= $pct_b ?>%</span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Panel 5: Semua klien ──────────────────────────────────── -->
<div class="card dash-section">
  <div class="card-header">
    <span class="card-title">Sebaran ISR per klien</span>
    <span class="card-hint"><?= number_format($n_klien,0,',','.') ?> klien · klik untuk expand</span>
  </div>
  <div style="padding:8px 14px 4px">
    <input type="search" id="clientSearch" placeholder="Cari nama klien..."
           class="dash-search" oninput="filterClients()">
  </div>
  <div class="dash-barlist dash-scroll" id="clientList"></div>
</div>

<script>
var TOTAL = <?= $total ?>;
var MAX_C = <?= $max_clnt ?>;
var CLIENTS = <?= json_encode($clnt_json, JSON_UNESCAPED_UNICODE) ?>;

function tog(id) {
  var el = document.getElementById(id);
  var ch = document.getElementById('ch_' + id);
  if (!el) return;
  var o = el.classList.toggle('open');
  if (ch) ch.classList.toggle('open', o);
}

function renderClients(list) {
  var h = '';
  list.forEach(function(cl, i) {
    var id = 'cl_' + i + '_' + cl.n.replace(/\W/g,'');
    h += '<div class="expand-row" onclick="tog(\'' + id + '\')">';
    h += '<div class="er-label"><i class="ti ti-chevron-right er-chev" id="ch_' + id + '"></i>' + cl.n + '</div>';
    h += '<div class="bar-track"><div class="bar-fill" style="width:' + cl.w + '%;background:#1D9E75"></div></div>';
    h += '<div class="er-val"><span class="er-abs">' + cl.c.toLocaleString('id-ID') + '</span><span class="er-pct">' + cl.p + '%</span></div>';
    h += '</div>';
    h += '<div class="expand-children" id="' + id + '">';
    cl.subs.forEach(function(sb) {
      var wsb = cl.c > 0 ? Math.round(sb.c / cl.c * 100) : 0;
      h += '<div class="expand-row">';
      h += '<div class="er-label indent1"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:' + sb.col + ';margin-right:5px;flex-shrink:0"></span>' + sb.svc + ' · ' + sb.sub + '</div>';
      h += '<div class="bar-track"><div class="bar-fill" style="width:' + wsb + '%;background:' + sb.col + ';opacity:.6"></div></div>';
      h += '<div class="er-val"><span class="er-abs">' + sb.c.toLocaleString('id-ID') + '</span><span class="er-pct">' + sb.p + '%</span></div>';
      h += '</div>';
    });
    h += '</div>';
  });
  document.getElementById('clientList').innerHTML = h || '<div style="padding:12px 0;color:var(--muted);font-size:12px">Tidak ditemukan</div>';
}

renderClients(CLIENTS);

function filterClients() {
  var q = document.getElementById('clientSearch').value.toLowerCase();
  renderClients(CLIENTS.filter(function(c) { return c.n.toLowerCase().includes(q); }));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>