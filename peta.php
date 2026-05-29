<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Peta Stasiun';

// ── Input sanitize ────────────────────────────────────────────
$q          = trim($_GET['q']          ?? '');
$service    = trim($_GET['service']    ?? '');
$subservice = trim($_GET['subservice'] ?? '');
$province   = trim($_GET['province']   ?? '');
$city       = trim($_GET['city']       ?? '');
$district   = trim($_GET['district']   ?? '');

// ── Dropdown options ──────────────────────────────────────────
$services  = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$provinces = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);

if ($service !== '') {
    $ss_stmt = $pdo->prepare("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL AND service = ? ORDER BY subservice");
    $ss_stmt->execute([$service]);
} else {
    $ss_stmt = $pdo->query("SELECT DISTINCT subservice FROM isr WHERE subservice IS NOT NULL ORDER BY subservice");
}
$subservices = $ss_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($province !== '') {
    $city_stmt = $pdo->prepare("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL AND province = ? ORDER BY city");
    $city_stmt->execute([$province]);
} else {
    $city_stmt = $pdo->query("SELECT DISTINCT city FROM isr WHERE city IS NOT NULL ORDER BY city");
}
$cities = $city_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($city !== '') {
    $dist_stmt = $pdo->prepare("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL AND city = ? ORDER BY district");
    $dist_stmt->execute([$city]);
} else {
    $dist_stmt = $pdo->query("SELECT DISTINCT district FROM isr WHERE district IS NOT NULL ORDER BY district");
}
$districts = $dist_stmt->fetchAll(PDO::FETCH_COLUMN);

$service_colors = [
    'Fixed Service'         => '#185FA5',
    'Land Mobile (public)'  => '#EF9F27',
    'Land Mobile (private)' => '#7F77DD',
    'Broadcast'             => '#E24B4A',
    'Aeronautical'          => '#1D9E75',
    'Other Services'        => '#888780',
];

// Build API query string from current filters
$api_params = array_filter([
    'q'          => $q,
    'service'    => $service,
    'subservice' => $subservice,
    'province'   => $province,
    'city'       => $city,
    'district'   => $district,
]);
$api_qs = $api_params ? '?' . http_build_query($api_params) : '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Peta Stasiun
  <div class="page-sub">Sebaran lokasi stasiun radio berdasarkan koordinat data SIMS</div>
</div>

<div class="card" id="peta-card">

  <!-- Filter (sama dengan Data ISR) -->
  <form method="get" action="" id="peta-form">
    <div style="padding:12px 14px;border-bottom:0.5px solid var(--border);display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end">

      <div class="form-group">
        <label>Cari nama klien / No. ISR</label>
        <div class="search-wrap">
          <i class="ti ti-search"></i>
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Ketik nama klien atau nomor lisensi...">
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

      <div class="form-group">
        <label>Kabupaten/Kota</label>
        <select name="city" class="auto-submit">
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

      <div style="display:flex;gap:6px;align-items:flex-end">
        <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i>Cari</button>
        <a href="peta.php" class="btn"><i class="ti ti-x"></i>Reset</a>
      </div>

    </div>
  </form>

  <!-- Map toolbar -->
  <div class="map-controls">
    <span id="map-count" style="font-size:12px;color:var(--muted)">Memuat...</span>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
      <button id="btn-cluster" class="btn" style="font-size:12px;padding:5px 10px">
        <i class="ti ti-layout-grid"></i> Cluster: ON
      </button>
    </div>
  </div>

  <!-- Legend -->
  <div style="padding:8px 14px;border-bottom:0.5px solid var(--border)">
    <div class="map-legend">
      <?php foreach ($service_colors as $svc => $color): ?>
      <div class="legend-item">
        <div class="legend-dot" style="background:<?= $color ?>"></div>
        <span style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($svc) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Map -->
  <div id="map"></div>

</div>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
(function () {

var SERVICE_COLORS = <?= json_encode($service_colors, JSON_UNESCAPED_UNICODE) ?>;
var API_QS = <?= json_encode($api_qs) ?>;

function makeIcon(color) {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">'
          + '<circle cx="8" cy="8" r="6" fill="' + color + '" stroke="#fff" stroke-width="2"/>'
          + '</svg>';
  return L.divIcon({ html: svg, className: '', iconSize: [16,16], iconAnchor: [8,8], popupAnchor: [0,-10] });
}

// ── Map fullscreen sizing ─────────────────────────────────────
function setMapHeight() {
  var card    = document.getElementById('peta-card');
  var mapEl   = document.getElementById('map');
  var toolbar = card.querySelector('.map-controls');
  var legend  = toolbar.nextElementSibling;
  var form    = card.querySelector('form');
  var cardTop = card.getBoundingClientRect().top + window.scrollY;
  var used    = form.offsetHeight + toolbar.offsetHeight + legend.offsetHeight;
  var available = window.innerHeight - cardTop - used - 2;
  mapEl.style.height = Math.max(280, available) + 'px';
}

var map = L.map('map').setView([-7.797, 110.370], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
  maxZoom: 18
}).addTo(map);

var clusterGroup = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 });
map.addLayer(clusterGroup);
var useCluster = true;
var plainGroup = L.layerGroup();

function loadMarkers() {
  clusterGroup.clearLayers();
  plainGroup.clearLayers();
  document.getElementById('map-count').textContent = 'Memuat...';

  fetch('<?= APP_BASE_URL ?>/api/geojson.php' + API_QS, { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.features) return;
      data.features.forEach(function(f) {
        var p      = f.properties;
        var latlng = [f.geometry.coordinates[1], f.geometry.coordinates[0]];
        var color  = SERVICE_COLORS[p.service] || '#888780';
        var popup  = '<strong>' + (p.stn || p.name || '—') + '</strong>'
          + '<div class="pop-meta">' + (p.service || '') + (p.freq ? ' · ' + p.freq.toLocaleString('id-ID') + ' MHz' : '') + '</div>'
          + '<div class="pop-meta">' + [p.district, p.city].filter(Boolean).join(', ') + '</div>'
          + (p.lic ? '<div class="pop-meta" style="font-family:monospace;font-size:10px">' + p.lic + '</div>' : '')
          + '<div style="margin-top:6px"><a href="<?= APP_BASE_URL ?>/detail.php?id=' + p.id + '" style="color:var(--blue);font-size:12px">Lihat detail →</a></div>';
        clusterGroup.addLayer(L.marker(latlng, { icon: makeIcon(color) }).bindPopup(popup));
        plainGroup.addLayer(L.marker(latlng, { icon: makeIcon(color) }).bindPopup(popup));
      });
      document.getElementById('map-count').textContent = data.total.toLocaleString('id-ID') + ' stasiun ditampilkan';
    })
    .catch(function() {
      document.getElementById('map-count').textContent = 'Gagal memuat data.';
    });
}

// Init sizing then load
setMapHeight();
window.addEventListener('resize', function() { setMapHeight(); map.invalidateSize(); });
loadMarkers();

// Cluster toggle
document.getElementById('btn-cluster').addEventListener('click', function() {
  useCluster = !useCluster;
  this.innerHTML = '<i class="ti ti-layout-grid"></i> Cluster: ' + (useCluster ? 'ON' : 'OFF');
  if (useCluster) { map.removeLayer(plainGroup); map.addLayer(clusterGroup); }
  else            { map.removeLayer(clusterGroup); map.addLayer(plainGroup); }
});

// Fix sidebar collapse/expand
var sidebar = document.getElementById('sidebar');
if (sidebar) {
  new MutationObserver(function() {
    setTimeout(function() { setMapHeight(); map.invalidateSize(); }, 260);
  }).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
}

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>