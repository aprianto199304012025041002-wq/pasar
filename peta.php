<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Peta Stasiun';

$services_list = [
    ''                      => ['label' => 'Semua layanan',      'color' => '#185FA5'],
    'Fixed Service'         => ['label' => 'Fixed Service',       'color' => '#185FA5'],
    'Land Mobile (public)'  => ['label' => 'LM Public',           'color' => '#EF9F27'],
    'Land Mobile (private)' => ['label' => 'LM Private',          'color' => '#7F77DD'],
    'Broadcast'             => ['label' => 'Broadcast',            'color' => '#E24B4A'],
    'Aeronautical'          => ['label' => 'Aeronautical',         'color' => '#1D9E75'],
    'Other Services'        => ['label' => 'Lainnya',              'color' => '#888780'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
  Peta Stasiun
  <div class="page-sub">Sebaran lokasi stasiun radio berdasarkan koordinat data SIMF</div>
</div>

<div class="card">

  <!-- Controls -->
  <div class="map-controls">
    <label style="font-size:12px;color:var(--muted);font-weight:500">Filter layanan:</label>
    <?php foreach ($services_list as $val => $meta): ?>
    <button class="filter-btn map-filter <?= $val === '' ? 'active' : '' ?>"
            data-service="<?= htmlspecialchars($val) ?>"
            style="<?= $val !== '' ? '--dot-color:' . $meta['color'] : '' ?>">
      <?php if ($val !== ''): ?>
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;
                     background:<?= $meta['color'] ?>;margin-right:4px;flex-shrink:0"></span>
      <?php endif; ?>
      <?= $meta['label'] ?>
    </button>
    <?php endforeach; ?>

    <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
      <span id="map-count" style="font-size:12px;color:var(--muted)">Memuat...</span>
      <button id="btn-cluster" class="btn" style="font-size:12px;padding:5px 10px">
        <i class="ti ti-layout-grid"></i> Cluster: ON
      </button>
    </div>
  </div>

  <!-- Legend -->
  <div style="padding:10px 18px;border-bottom:0.5px solid var(--border)">
    <div class="map-legend">
      <?php foreach (array_slice($services_list, 1) as $val => $meta): ?>
      <div class="legend-item">
        <div class="legend-dot" style="background:<?= $meta['color'] ?>"></div>
        <span style="font-size:12px;color:var(--muted)"><?= $meta['label'] ?></span>
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

var SERVICE_COLORS = {
  'Fixed Service':          '#185FA5',
  'Land Mobile (public)':   '#EF9F27',
  'Land Mobile (private)':  '#7F77DD',
  'Broadcast':              '#E24B4A',
  'Aeronautical':           '#1D9E75',
  'Other Services':         '#888780',
};

function makeIcon(color) {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">'
          + '<circle cx="8" cy="8" r="6" fill="' + color + '" stroke="#fff" stroke-width="2"/>'
          + '</svg>';
  return L.divIcon({
    html: svg, className: '', iconSize: [16, 16], iconAnchor: [8, 8], popupAnchor: [0, -10]
  });
}

// Init map — center Yogyakarta
var map = L.map('map').setView([-7.797, 110.370], 10);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
  maxZoom: 18
}).addTo(map);

// Cluster group
var clusterGroup = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 });
map.addLayer(clusterGroup);
var useCluster = true;
var plainGroup  = L.layerGroup();
var currentMarkers = [];
var currentService = '';

function loadMarkers(service) {
  clusterGroup.clearLayers();
  plainGroup.clearLayers();
  currentMarkers = [];
  document.getElementById('map-count').textContent = 'Memuat...';

  var url = '<?= APP_BASE_URL ?>/api/geojson.php' + (service ? '?service=' + encodeURIComponent(service) : '');

  fetch(url, { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.features) return;

      data.features.forEach(function(f) {
        var p    = f.properties;
        var latlng = [f.geometry.coordinates[1], f.geometry.coordinates[0]];
        var color  = SERVICE_COLORS[p.service] || '#888780';
        var icon   = makeIcon(color);

        var popup = '<strong>' + (p.stn || p.name || '—') + '</strong>'
          + '<div class="pop-meta">' + (p.service || '') + (p.freq ? ' · ' + p.freq.toLocaleString('id-ID') + ' MHz' : '') + '</div>'
          + '<div class="pop-meta">' + [p.district, p.city].filter(Boolean).join(', ') + '</div>'
          + (p.lic ? '<div class="pop-meta" style="font-family:monospace;font-size:10px">' + p.lic + '</div>' : '')
          + '<div style="margin-top:6px"><a href="<?= APP_BASE_URL ?>/detail.php?id=' + p.id + '" style="color:var(--blue);font-size:12px">Lihat detail →</a></div>';

        var marker = L.marker(latlng, { icon: icon }).bindPopup(popup);
        currentMarkers.push(marker);
        clusterGroup.addLayer(marker);
        plainGroup.addLayer(L.marker(latlng, { icon: icon }).bindPopup(popup));
      });

      document.getElementById('map-count').textContent = data.total.toLocaleString('id-ID') + ' stasiun ditampilkan';
    })
    .catch(function() {
      document.getElementById('map-count').textContent = 'Gagal memuat data.';
    });
}

// Initial load
loadMarkers('');

// Filter buttons
document.querySelectorAll('.map-filter').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.map-filter').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    currentService = btn.dataset.service;
    loadMarkers(currentService);
  });
});

// Cluster toggle
document.getElementById('btn-cluster').addEventListener('click', function() {
  useCluster = !useCluster;
  this.innerHTML = '<i class="ti ti-layout-grid"></i> Cluster: ' + (useCluster ? 'ON' : 'OFF');
  if (useCluster) {
    map.removeLayer(plainGroup);
    map.addLayer(clusterGroup);
  } else {
    map.removeLayer(clusterGroup);
    map.addLayer(plainGroup);
  }
});

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
