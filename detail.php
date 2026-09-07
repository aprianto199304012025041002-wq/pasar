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
    echo "<tr>
        <td style='color:#6b7280;width:110px;max-width:110px;padding:5px 10px;vertical-align:top;font-size:11px;line-height:1.3'>$label</td>
        <td style='padding:5px 10px;font-size:11px;color:#1e1b4b;word-break:break-word'>$val</td>
    </tr>";
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.detail-card {
    background: rgba(255,255,255,0.93);
    border: 0.5px solid #c7d2fe;
    border-radius: 12px;
    overflow: hidden;
}
.detail-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-bottom: 0.5px solid #c7d2fe;
    font-size: 13px;
    font-weight: 600;
}
.detail-card table tr:nth-child(even) { background: #F5F7FF; }
</style>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="data.php" class="btn">
    <i class="ti ti-arrow-left"></i>Kembali
  </a>
  <div>
    <div class="page-title" style="margin-bottom:0"><?= htmlspecialchars($d['stn_name'] ?: $d['clnt_name']) ?></div>
    <div class="page-sub"><?= htmlspecialchars($d['curr_lic_num'] ?? '-') ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">

  <!-- Info Klien -->
  <div class="detail-card">
    <div class="detail-card-header">
      <i class="ti ti-building" style="font-size:15px"></i>Informasi Klien
    </div>
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('Nama Klien',     $d['clnt_name']);
      row_detail('Client ID',      $d['clnt_id']);
      row_detail('No. SIMS',       $d['no_simf']);
      row_detail('Application ID', $d['appl_id']);
      row_detail('Status',         $d['status_simf']);
      row_detail('UPT',            $d['upt']);
      ?>
    </table>
  </div>

  <!-- Info Lisensi -->
  <div class="detail-card">
    <div class="detail-card-header">
      <i class="ti ti-license" style="font-size:15px"></i>Lisensi & Masa Berlaku
    </div>
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

  <!-- Info Frekuensi -->
  <div class="detail-card">
    <div class="detail-card-header">
      <i class="ti ti-wave-sine" style="font-size:15px"></i>Teknis Frekuensi
    </div>
    <table style="width:100%;border-collapse:collapse">
      <?php
      row_detail('Layanan',        $d['service']);
      row_detail('Sub-layanan',    $d['subservice']);
      row_detail('Trans. Type',    $d['trans_type']);
      row_detail('Frekuensi (MHz)', $d['freq'] !== null ? number_format($d['freq'], 3, ',', '.') : null);
      row_detail('Frekuensi Pasang', $d['freq_pair'] !== null ? number_format($d['freq_pair'], 3, ',', '.') : null);
      row_detail('ERP (dBm)',      $d['erp_pwr_dbm']);
      row_detail('EQ Power (W)',   $d['eq_pwr']);
      row_detail('Bandwidth',      $d['bwidth'] ?? null);
      row_detail('Zona',           $d['zona']);
      row_detail('Azimuth',        $d['azim']);
      row_detail('Tinggi Antena',  $d['hgt_ant'] ? $d['hgt_ant'] . ' m' : null);
      row_detail('H-ASL',          $d['h_asl'] ? $d['h_asl'] . ' m' : null);
      row_detail('Jml. Set',       $d['num_sets']);
      ?>
    </table>
  </div>

  <!-- Info Perangkat -->
  <div class="detail-card">
    <div class="detail-card-header">
      <i class="ti ti-device-desktop" style="font-size:15px"></i>Perangkat & Lokasi
    </div>
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
      <tr><td colspan="2" style="padding:8px 12px;border-top:0.5px solid #c7d2fe">
        <div style="font-size:11px;margin-bottom:2px" class="td-muted">Nama Stasiun</div>
        <div style="font-size:12px"><?= htmlspecialchars($d['stn_name'] ?? '—') ?></div>
      </td></tr>
      <tr style="background:#F5F7FF"><td colspan="2" style="padding:8px 12px">
        <div style="font-size:11px;margin-bottom:2px" class="td-muted">Alamat Stasiun</div>
        <div style="font-size:12px"><?= htmlspecialchars($d['stn_addr'] ?? '—') ?></div>
      </td></tr>
      <tr><td colspan="2" style="padding:8px 12px">
        <div style="font-size:11px;margin-bottom:2px" class="td-muted">Lokasi Administratif</div>
        <div style="font-size:12px">
          <?= htmlspecialchars(implode(', ', array_filter([$d['village'], $d['district'], $d['city'], $d['province']]))) ?>
        </div>
      </td></tr>
      <tr style="background:#F5F7FF"><td colspan="2" style="padding:8px 12px">
        <div style="font-size:11px;margin-bottom:2px" class="td-muted">Koordinat</div>
        <div style="font-size:12px;font-family:monospace;color:#4F46E5"><?= $d['sid_lat'] ?>, <?= $d['sid_long'] ?></div>
      </td></tr>
    </table>
  </div>

</div>

<!-- Mini map -->
<?php if ($d['sid_lat'] && $d['sid_long']): ?>
<div class="detail-card" style="margin-top:12px">
  <div class="detail-card-header" style="background:#EFF6FF;color:#1e40af">
    <i class="ti ti-map-pin" style="font-size:15px;color:#2563EB"></i>Lokasi Stasiun
  </div>
  <div id="minimap" style="height:280px;border-radius:0 0 12px 12px;overflow:hidden"></div>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var lat = <?= floatval($d['sid_lat']) ?>, lng = <?= floatval($d['sid_long']) ?>;
var map = L.map('minimap').setView([lat, lng], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  { attribution: '© OpenStreetMap' }).addTo(map);
L.marker([lat, lng]).addTo(map)
  .bindPopup('<strong>' + <?= json_encode(htmlspecialchars($d['stn_name'] ?: $d['clnt_name'])) ?> + '</strong><br>' + <?= json_encode(htmlspecialchars($d['service'] ?? '')) ?>).openPopup();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

