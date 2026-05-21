<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: data.php'); exit; }

$row = $pdo->prepare("SELECT * FROM isr WHERE id = ?");
$row->execute([$id]);
$d = $row->fetch();
if (!$d) { header('Location: data.php'); exit; }

$page_title = 'Detail ISR — ' . ($d['stn_name'] ?: $d['clnt_name']);

function row_detail(string $label, $val): void {
    if ($val === null || $val === '') $val = '<span style="color:var(--muted)">—</span>';
    else $val = htmlspecialchars($val);
    echo "<tr><td style='color:var(--muted);width:200px;padding:8px 12px;vertical-align:top;font-size:12px'>$label</td>
               <td style='padding:8px 12px;font-size:13px'>$val</td></tr>";
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="data.php" class="btn"><i class="ti ti-arrow-left"></i>Kembali</a>
  <div>
    <div class="page-title" style="margin-bottom:0"><?= htmlspecialchars($d['stn_name'] ?: $d['clnt_name']) ?></div>
    <div class="page-sub"><?= htmlspecialchars($d['curr_lic_num'] ?? '-') ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

  <!-- Info Entitas -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="ti ti-building" style="font-size:15px;margin-right:6px"></i>Informasi Entitas</span></div>
    <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('Nama Entitas',    $d['clnt_name']);
      row_detail('Client ID',       $d['clnt_id']);
      row_detail('No. SIMF',        $d['no_simf']);
      row_detail('Application ID',  $d['appl_id']);
      row_detail('Status',          $d['status_simf']);
      row_detail('UPT',             $d['upt']);
      ?>
    </table>
    </div>
  </div>

  <!-- Info Lisensi -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="ti ti-license" style="font-size:15px;margin-right:6px"></i>Lisensi & Masa Berlaku</span></div>
    <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('No. Lisensi',    $d['curr_lic_num']);
      row_detail('Tgl. Permohonan',$d['appl_date']);
      row_detail('Tgl. Lisensi',   $d['licence_date']);
      row_detail('Tgl. Pembaruan', $d['renewal_date']);
      row_detail('Berlaku s/d',    $d['validity_date']);
      row_detail('Masa Laku',      $d['masa_laku']);
      row_detail('Umur ISR (thn)', $d['umur_isr']);
      ?>
    </table>
    </div>
  </div>

  <!-- Info Frekuensi -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="ti ti-wave-sine" style="font-size:15px;margin-right:6px"></i>Teknis Frekuensi</span></div>
    <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('Layanan',      $d['service']);
      row_detail('Sub-layanan',  $d['subservice']);
      row_detail('Trans. Type',  $d['trans_type']);
      row_detail('Frekuensi (MHz)',  $d['freq'] !== null ? number_format($d['freq'], 3, ',', '.') : null);
      row_detail('Frekuensi Pasang', $d['freq_pair'] !== null ? number_format($d['freq_pair'], 3, ',', '.') : null);
      row_detail('ERP (dBm)',    $d['erp_pwr_dbm']);
      row_detail('EQ Power (W)', $d['eq_pwr']);
      row_detail('Bandwidth',    $d['bwidth'] ?? null);
      row_detail('Zona',         $d['zona']);
      row_detail('Azimuth',      $d['azim']);
      row_detail('Tinggi Antena',$d['hgt_ant'] ? $d['hgt_ant'] . ' m' : null);
      row_detail('H-ASL',        $d['h_asl'] ? $d['h_asl'] . ' m' : null);
      row_detail('Jml. Set',     $d['num_sets']);
      ?>
    </table>
    </div>
  </div>

  <!-- Info Perangkat -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="ti ti-device-desktop" style="font-size:15px;margin-right:6px"></i>Perangkat & Lokasi</span></div>
    <div style="overflow:auto">
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('Produsen Perangkat', $d['eq_mfr']);
      row_detail('Model Perangkat',    $d['eq_mdl']);
      row_detail('Produsen Antena',    $d['ant_mfr']);
      row_detail('Model Antena',       $d['ant_mdl']);
      row_detail('Jenis Stasiun',      $d['stn_type']);
      row_detail('Jenis Perangkat',    $d['equip_type']);
      row_detail('BHP',                $d['bhp'] ?? null);
      ?>
      <tr><td colspan="2" style="padding:8px 12px;border-top:0.5px solid var(--border)">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Nama Stasiun</div>
        <div style="font-size:13px"><?= htmlspecialchars($d['stn_name'] ?? '—') ?></div>
      </td></tr>
      <tr><td colspan="2" style="padding:8px 12px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Alamat Stasiun</div>
        <div style="font-size:13px"><?= htmlspecialchars($d['stn_addr'] ?? '—') ?></div>
      </td></tr>
      <tr><td colspan="2" style="padding:8px 12px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Lokasi Administratif</div>
        <div style="font-size:13px">
          <?= htmlspecialchars(implode(', ', array_filter([$d['village'], $d['district'], $d['city'], $d['province']]))) ?>
        </div>
      </td></tr>
      <tr><td colspan="2" style="padding:8px 12px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Koordinat</div>
        <div style="font-size:13px;font-family:monospace">
          <?= $d['sid_lat'] ?>, <?= $d['sid_long'] ?>
        </div>
      </td></tr>
    </table>
    </div>
  </div>

</div>

<!-- Mini map -->
<?php if ($d['sid_lat'] && $d['sid_long']): ?>
<div class="card" style="margin-top:16px">
  <div class="card-header"><span class="card-title"><i class="ti ti-map-pin" style="font-size:15px;margin-right:6px"></i>Lokasi Stasiun</span></div>
  <div id="minimap" style="height:280px;border-radius:0 0 var(--radius-lg) var(--radius-lg);overflow:hidden"></div>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var lat = <?= floatval($d['sid_lat']) ?>, lng = <?= floatval($d['sid_long']) ?>;
var map = L.map('minimap').setView([lat, lng], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  { attribution: '© OpenStreetMap' }).addTo(map);
L.marker([lat, lng]).addTo(map)
  .bindPopup('<strong><?= addslashes(htmlspecialchars($d['stn_name'] ?: $d['clnt_name'])) ?></strong><br><?= addslashes(htmlspecialchars($d['service'])) ?>').openPopup();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
