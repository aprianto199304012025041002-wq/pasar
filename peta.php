<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Peta Stasiun';

// ── Input sanitize ────────────────────────────────────────────
$q               = trim($_GET['q']               ?? '');
$service         = trim($_GET['service']         ?? '');
$subservice      = trim($_GET['subservice']      ?? '');
$area_of_service = trim($_GET['area_of_service'] ?? '');
$province        = trim($_GET['province']        ?? '');
$city            = trim($_GET['city']            ?? '');
$district        = trim($_GET['district']        ?? '');

// ── Dropdown options ──────────────────────────────────────────
$services         = $pdo->query("SELECT DISTINCT service FROM isr WHERE service IS NOT NULL ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);
$areas_of_service = $pdo->query("SELECT DISTINCT area_of_service FROM isr WHERE area_of_service IS NOT NULL ORDER BY area_of_service")->fetchAll(PDO::FETCH_COLUMN);
$provinces        = $pdo->query("SELECT DISTINCT province FROM isr WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);

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
    'q'               => $q,
    'service'         => $service,
    'subservice'      => $subservice,
    'area_of_service' => $area_of_service,
    'province'        => $province,
    'city'            => $city,
    'district'        => $district,
], fn($v) => $v !== '');
$api_qs = $api_params ? '?' . http_build_query($api_params) : '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Peta Stasiun
  <div class="page-sub">Sebaran lokasi stasiun radio berdasarkan koordinat data SIMS</div>
</div>

<div class="card" id="peta-card">

  <!-- Filter + Kontrol Peta dalam satu drawer -->
  <?php
  $peta_filters = array_filter([$q, $service, $subservice, $area_of_service, $province, $city, $district]);
  $peta_count   = count($peta_filters);
  $peta_summary = [];
  if ($q !== '')               $peta_summary[] = '"' . substr(htmlspecialchars($q), 0, 20) . (strlen(htmlspecialchars($q)) > 20 ? '…' : '') . '"';
  if ($service !== '')         $peta_summary[] = htmlspecialchars($service);
  if ($area_of_service !== '') $peta_summary[] = htmlspecialchars($area_of_service);
  if ($province !== '')        $peta_summary[] = htmlspecialchars($province);
  if ($city !== '')            $peta_summary[] = htmlspecialchars($city);
  $peta_summary_text = implode(', ', array_slice($peta_summary, 0, 2)) . (count($peta_summary) > 2 ? ', …' : '');
  ?>
  <form method="get" action="" id="peta-form">
    <div class="filter-compact" id="peta-filter-compact">

      <!-- Toggle bar -->
      <div class="filter-toggle-bar" id="peta-filter-toggle" role="button" aria-expanded="true">
        <span class="filter-toggle-label">
          <i class="ti ti-filter" style="font-size:13px;flex-shrink:0"></i>
          <span>Filter &amp; Kontrol</span>
          <?php if ($peta_count > 0): ?>
          <span class="filter-active-summary">&mdash; <?= $peta_summary_text ?></span>
          <?php endif; ?>
        </span>
        <span id="map-count" style="font-size:11px;color:var(--muted);margin-left:auto;margin-right:4px;white-space:nowrap">Memuat...</span>
        <?php if ($peta_count > 0): ?>
        <span style="font-size:10px;background:var(--blue);color:#fff;border-radius:10px;padding:1px 6px;flex-shrink:0">
          <?= $peta_count ?>
        </span>
        <?php endif; ?>
        <i class="ti ti-chevron-down filter-toggle-chevron"></i>
      </div>

      <div class="filter-body">

        <!-- Baris pencarian -->
        <div class="filter-search-row">
          <div class="search-wrap">
            <i class="ti ti-search"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
                   placeholder="Cari nama klien, stasiun, No. ISR, atau frekuensi (MHz)...">
          </div>
        </div>

        <!-- Baris dropdown filter -->
        <div class="filter-dropdowns-row">
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
            <label>Kab/Kota</label>
            <select name="city" class="auto-submit">
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
            <a href="peta.php" class="btn"><i class="ti ti-x"></i>Reset</a>
          </div>
        </div>

        <!-- Divider -->
        <div style="border-top:1px solid var(--border);margin:8px 0 10px"></div>

        <!-- Kontrol peta: tombol + legenda dalam satu baris -->
        <div class="peta-controls-row">
          <!-- Tombol aksi -->
          <div class="peta-btn-group">
            <button type="button" id="btn-myloc" class="btn btn-sm">
              <i class="ti ti-current-location"></i> Lokasi Saya
            </button>
            <button type="button" id="btn-cluster" class="btn btn-sm">
              <i class="ti ti-layout-grid"></i> Cluster: ON
            </button>
          </div>
          <!-- Legenda service -->
          <div class="map-legend">
            <?php foreach ($service_colors as $svc => $color): ?>
            <div class="legend-item">
              <div class="legend-dot" style="background:<?= $color ?>"></div>
              <span><?= htmlspecialchars($svc) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div><!-- .filter-body -->
    </div><!-- .filter-compact -->
  </form>

  <!-- Panel radius — muncul setelah GPS didapat -->
  <div id="radius-panel" style="display:none;flex-direction:column;gap:6px;padding:8px 12px;background:var(--accent-glow);border-bottom:1px solid var(--border-hi)">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <i class="ti ti-radar" style="color:var(--accent);font-size:15px;flex-shrink:0"></i>
      <span style="font-size:12px;color:var(--accent);font-weight:600;flex-shrink:0;font-family:var(--mono);letter-spacing:.5px">Radius dari posisi saya</span>
      <span id="radius-count" style="font-size:12px;color:var(--muted-hi);flex:1;min-width:0"></span>
      <button id="btn-clear-loc" type="button" class="btn" style="font-size:11px;padding:3px 8px;flex-shrink:0;color:var(--red);border-color:var(--red);margin-left:auto">
        <i class="ti ti-x"></i> Hapus
      </button>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <input type="range" id="radius-slider" min="0.5" max="100" step="0.5" value="10"
             style="flex:1;min-width:80px;accent-color:var(--accent)">
      <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <input type="number" id="radius-input" min="0.5" max="500" step="0.5" value="10"
               style="width:64px;font-size:12px;padding:3px 6px;border:1px solid var(--border-hi);border-radius:var(--radius-sm);text-align:right;background:var(--surface2);color:var(--text)">
        <span style="font-size:12px;color:#6366f1;flex-shrink:0">km</span>
      </div>
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

// ── State ─────────────────────────────────────────────────────
var allFeatures  = [];
var geoTotal     = 0;
var geoTruncated = false;
var userLat      = null;
var userLng      = null;
var radiusKm     = 10;
var locMarker    = null;
var locCircle    = null;
var locWatchId   = null;

// ── Icon helpers ──────────────────────────────────────────────
function makeIcon(color) {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">'
          + '<circle cx="8" cy="8" r="6" fill="' + color + '" stroke="#fff" stroke-width="2"/>'
          + '</svg>';
  return L.divIcon({ html: svg, className: '', iconSize: [16,16], iconAnchor: [8,8], popupAnchor: [0,-10] });
}

function makeUserIcon() {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">'
          + '<circle cx="10" cy="10" r="7" fill="#2563EB" stroke="#fff" stroke-width="2.5"/>'
          + '<circle cx="10" cy="10" r="3" fill="#fff"/>'
          + '</svg>';
  return L.divIcon({ html: svg, className: '', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-12] });
}

// ── Haversine ─────────────────────────────────────────────────
function haversine(lat1, lng1, lat2, lng2) {
  var R  = 6371;
  var dL = (lat2 - lat1) * Math.PI / 180;
  var dG = (lng2 - lng1) * Math.PI / 180;
  var a  = Math.sin(dL/2)*Math.sin(dL/2)
          + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)
          * Math.sin(dG/2)*Math.sin(dG/2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// ── Map sizing — peta full dari bawah drawer sampai bawah viewport ──
function setMapHeight() {
  var card     = document.getElementById('peta-card');
  var mapEl    = document.getElementById('map');
  var form     = card.querySelector('form');
  var radPanel = document.getElementById('radius-panel');
  var cardTop  = card.getBoundingClientRect().top + window.scrollY;
  var used     = form.offsetHeight
               + (radPanel.style.display !== 'none' ? radPanel.offsetHeight : 0);
  var available = window.innerHeight - cardTop - used - 2;
  mapEl.style.height = Math.max(300, available) + 'px';
}

// ── Map init ──────────────────────────────────────────────────
var map = L.map('map').setView([-7.797, 110.370], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
  maxZoom: 18
}).addTo(map);

var clusterGroup = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 });
map.addLayer(clusterGroup);
var useCluster = true;
var plainGroup = L.layerGroup();

// ── Build popup string ────────────────────────────────────────
function buildPopup(p, distKm) {
  var distStr = (distKm !== null)
    ? '<div class="pop-meta" style="color:var(--accent);font-weight:600">'
        + '<i class="ti ti-map-pin" style="font-size:10px"></i> '
        + (distKm < 1 ? (distKm * 1000).toFixed(0) + ' m' : distKm.toFixed(2) + ' km') + ' dari lokasi Anda'
      + '</div>'
    : '';
  return '<strong>' + (p.stn || p.name || '—') + '</strong>'
    + distStr
    + '<div class="pop-meta">' + (p.service || '') + (p.freq ? ' · ' + p.freq.toLocaleString('id-ID') + ' MHz' : '') + '</div>'
    + '<div class="pop-meta">' + [p.district, p.city].filter(Boolean).join(', ') + '</div>'
    + (p.lic ? '<div class="pop-meta" style="font-family:monospace;font-size:10px">' + p.lic + '</div>' : '')
    + '<div style="margin-top:6px"><a href="<?= APP_BASE_URL ?>/detail.php?id=' + p.id + '" style="color:var(--blue);font-size:12px">Lihat detail →</a></div>';
}

// ── Render markers ────────────────────────────────────────────
function renderMarkers() {
  clusterGroup.clearLayers();
  plainGroup.clearLayers();

  var filtered = [];
  var hasLoc   = userLat !== null && userLng !== null;

  allFeatures.forEach(function(f) {
    var p      = f.properties;
    var lat    = f.geometry.coordinates[1];
    var lng    = f.geometry.coordinates[0];
    var latlng = [lat, lng];
    var distKm = null;

    if (hasLoc) {
      distKm = haversine(userLat, userLng, lat, lng);
      if (distKm > radiusKm) return;
    }

    filtered.push(f);
    var color  = SERVICE_COLORS[p.service] || '#888780';
    var popup  = buildPopup(p, distKm);
    clusterGroup.addLayer(L.marker(latlng, { icon: makeIcon(color) }).bindPopup(popup));
    plainGroup.addLayer(L.marker(latlng, { icon: makeIcon(color) }).bindPopup(popup));
  });

  var countEl = document.getElementById('map-count');
  if (hasLoc) {
    countEl.textContent =
      filtered.length.toLocaleString('id-ID') + ' stasiun dalam ' + radiusKm + ' km';
    document.getElementById('radius-count').textContent =
      filtered.length.toLocaleString('id-ID') + ' stasiun ditemukan';
  } else if (geoTruncated) {
    countEl.innerHTML =
      allFeatures.length.toLocaleString('id-ID') + ' dari ' +
      geoTotal.toLocaleString('id-ID') + ' ditampilkan &nbsp;' +
      '<span style="color:#dc2626;font-size:11px" title="Gunakan filter untuk mempersempit hasil">&#9888; dibatasi</span>';
  } else {
    countEl.textContent =
      allFeatures.length.toLocaleString('id-ID') + ' stasiun';
  }
}

// ── Fetch GeoJSON ─────────────────────────────────────────────
function loadMarkers() {
  clusterGroup.clearLayers();
  plainGroup.clearLayers();
  document.getElementById('map-count').textContent = 'Memuat...';

  fetch('<?= APP_BASE_URL ?>/api/geojson.php' + API_QS, { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      allFeatures  = data.features || [];
      geoTotal     = data.total   || 0;
      geoTruncated = data.truncated || false;
      renderMarkers();
    })
    .catch(function() {
      document.getElementById('map-count').textContent = 'Gagal memuat data.';
    });
}

// ── Update marker lokasi user & lingkaran radius ──────────────
function updateLocLayer() {
  if (locMarker) { map.removeLayer(locMarker); locMarker = null; }
  if (locCircle) { map.removeLayer(locCircle); locCircle = null; }
  if (userLat === null) return;
  locCircle = L.circle([userLat, userLng], {
    radius: radiusKm * 1000,
    color: 'var(--accent)', fillColor: '#00e5ff', fillOpacity: 0.07,
    weight: 1.5, dashArray: '5,5'
  }).addTo(map);
  locMarker = L.marker([userLat, userLng], { icon: makeUserIcon(), zIndexOffset: 1000 })
    .addTo(map)
    .bindPopup('<strong>Posisi Anda</strong><div class="pop-meta" style="font-family:monospace;font-size:10px">'
      + userLat.toFixed(6) + ', ' + userLng.toFixed(6) + '</div>');
}

// ── Geolocation ───────────────────────────────────────────────
function onLocSuccess(pos) {
  userLat = pos.coords.latitude;
  userLng = pos.coords.longitude;
  var panel = document.getElementById('radius-panel');
  panel.style.display = 'flex';
  setMapHeight();
  map.invalidateSize();
  map.setView([userLat, userLng], 13);
  updateLocLayer();
  renderMarkers();
  var btn = document.getElementById('btn-myloc');
  btn.innerHTML = '<i class="ti ti-current-location-filled"></i> Lokasi Aktif';
  btn.style.borderColor = '#2563EB';
  btn.style.color = '#2563EB';
}

function onLocError(err) {
  var msg = {
    1: 'Akses lokasi ditolak. Izinkan lokasi di browser.',
    2: 'Posisi tidak dapat ditentukan.',
    3: 'Permintaan lokasi timeout.'
  }[err.code] || 'Gagal mendapatkan lokasi.';
  alert(msg);
  var btn = document.getElementById('btn-myloc');
  btn.innerHTML = '<i class="ti ti-current-location"></i> Lokasi Saya';
  btn.style.borderColor = '';
  btn.style.color = '';
}

document.getElementById('btn-myloc').addEventListener('click', function() {
  if (!navigator.geolocation) { alert('Browser tidak mendukung geolocation.'); return; }
  this.innerHTML = '<i class="ti ti-loader-2"></i> Mencari...';
  navigator.geolocation.getCurrentPosition(onLocSuccess, onLocError, {
    enableHighAccuracy: true, timeout: 10000, maximumAge: 30000
  });
});

// ── Hapus lokasi ──────────────────────────────────────────────
document.getElementById('btn-clear-loc').addEventListener('click', function() {
  userLat = userLng = null;
  if (locWatchId !== null) { navigator.geolocation.clearWatch(locWatchId); locWatchId = null; }
  updateLocLayer();
  renderMarkers();
  var panel = document.getElementById('radius-panel');
  panel.style.display = 'none';
  setMapHeight();
  map.invalidateSize();
  var btn = document.getElementById('btn-myloc');
  btn.innerHTML = '<i class="ti ti-current-location"></i> Lokasi Saya';
  btn.style.borderColor = '';
  btn.style.color = '';
});

// ── Radius controls ───────────────────────────────────────────
var slider   = document.getElementById('radius-slider');
var numInput = document.getElementById('radius-input');

function applyRadius(val) {
  var v = parseFloat(val);
  if (isNaN(v) || v <= 0) return;
  radiusKm = v;
  slider.value  = Math.min(v, parseFloat(slider.max));
  numInput.value = v;
  updateLocLayer();
  renderMarkers();
}

slider.addEventListener('input', function() { applyRadius(this.value); });
numInput.addEventListener('change', function() { applyRadius(this.value); });
numInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') applyRadius(this.value);
});

// ── Cluster toggle ────────────────────────────────────────────
document.getElementById('btn-cluster').addEventListener('click', function() {
  useCluster = !useCluster;
  this.innerHTML = '<i class="ti ti-layout-grid"></i> Cluster: ' + (useCluster ? 'ON' : 'OFF');
  if (useCluster) { map.removeLayer(plainGroup); map.addLayer(clusterGroup); }
  else            { map.removeLayer(clusterGroup); map.addLayer(plainGroup); }
});

// ── Init ──────────────────────────────────────────────────────
setMapHeight();
window.addEventListener('resize', function() { setMapHeight(); map.invalidateSize(); });
loadMarkers();

// Fix sidebar collapse/expand
var sidebar = document.getElementById('sidebar');
if (sidebar) {
  new MutationObserver(function() {
    setTimeout(function() { setMapHeight(); map.invalidateSize(); }, 260);
  }).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
}

// Drawer buka/tutup juga resize peta
var filterCompact = document.getElementById('peta-filter-compact');
if (filterCompact) {
  new MutationObserver(function() {
    setTimeout(function() { setMapHeight(); map.invalidateSize(); }, 280);
  }).observe(filterCompact, { attributes: true, attributeFilter: ['class'] });
}

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
