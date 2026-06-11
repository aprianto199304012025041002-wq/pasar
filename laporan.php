<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Laporan';

define('TARGET_TABLE', 'isr');
define('LOG_TABLE',    'upload_log');

$KNOWN_COLUMNS = [
    'CLNT_ID','NO_SIMF','APPL_ID','SITE_ID','CLNT_NAME','STATUS_SIMF','CORR_ADDR',
    'AP_REQUEST_TYPE','SERVICE','SUBSERVICE','TRANS_TYPE','FREQ','FREQ_PAIR',
    'ERP_PWR_DBM','EQ_PWR','GAIN','LOSS','BWIDTH','IB','IP','HDDP','HDLP','ZONA',
    'STN_TYPE','EQUIP_TYPE','BHP','EQ_MFR','EQ_MDL','ANT_MFR','ANT_MDL','HGT_ANT',
    'AZIM','ELEV_ANGLE','MASTER_PLZN_CODE','EMIS_CLASS_1','STN_NAME','STN_ADDR',
    'SID_LONG','SID_LAT','LINK_ID','CIRCUIT_LEN','LAT_DEG','LAT_MIN','LAT_SEC',
    'LAT_DIR_IND','LONG_DEG','LONG_MIN','LONG_SEC','LONG_DIR_IND','AREA_OF_SERVICE',
];

function _read_xlsx(string $path): array {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new Exception('Tidak bisa membuka file XLSX.');
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $ss = simplexml_load_string($ssXml);
        foreach ($ss->si as $si) {
            $text = '';
            foreach ($si->r as $r) $text .= (string)$r->t;
            if ($text === '') $text = (string)$si->t;
            $sharedStrings[] = $text;
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) throw new Exception('sheet1 tidak ditemukan di XLSX.');
    $sheet = simplexml_load_string($sheetXml);
    $rows  = [];
    foreach ($sheet->sheetData->row as $row) {
        $rowData = []; $prevCol = -1;
        foreach ($row->c as $cell) {
            $colIdx = _col_to_index(preg_replace('/[0-9]/', '', (string)$cell['r']));
            while (++$prevCol < $colIdx) $rowData[] = '';
            $type  = (string)$cell['t'];
            $value = (string)$cell->v;
            if ($type === 's')             $value = $sharedStrings[(int)$value] ?? '';
            elseif ($type === 'inlineStr') $value = (string)$cell->is->t;
            $rowData[] = $value; $prevCol = $colIdx;
        }
        $rows[] = $rowData;
    }
    return $rows;
}

function _col_to_index(string $col): int {
    $col = strtoupper($col); $idx = 0;
    for ($i = 0; $i < strlen($col); $i++)
        $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
    return $idx - 1;
}

function _read_csv(string $path): array {
    $rows = [];
    if (($fh = fopen($path, 'r')) === false) throw new Exception('Tidak bisa membuka file CSV.');
    while (($line = fgetcsv($fh, 0, ',')) !== false) $rows[] = $line;
    fclose($fh);
    if (!empty($rows) && count($rows[0]) === 1) {
        $rows = [];
        if (($fh = fopen($path, 'r')) === false) throw new Exception('Tidak bisa membuka file CSV.');
        while (($line = fgetcsv($fh, 0, ';')) !== false) $rows[] = $line;
        fclose($fh);
    }
    return $rows;
}

function _ensure_table(PDO $pdo, string $table, array $columns): void {
    $stmt = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
    if ($stmt->rowCount() > 0) return;
    $colDefs = ['`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'];
    foreach ($columns as $col) $colDefs[] = '`' . $col . '` TEXT';
    $pdo->exec('CREATE TABLE IF NOT EXISTS `' . $table . '` (' . implode(', ', $colDefs) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function _fmt_bytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS `' . LOG_TABLE . '` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `uploaded_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `user_id`     VARCHAR(64)  NOT NULL DEFAULT \'\',
        `user_name`   VARCHAR(128) NOT NULL DEFAULT \'\',
        `user_email`  VARCHAR(128) NOT NULL DEFAULT \'\',
        `user_role`   VARCHAR(32)  NOT NULL DEFAULT \'\',
        `file_name`   VARCHAR(255) NOT NULL DEFAULT \'\',
        `file_type`   VARCHAR(10)  NOT NULL DEFAULT \'\',
        `file_size`   INT UNSIGNED NOT NULL DEFAULT 0,
        `row_count`   INT UNSIGNED NULL,
        `status`      ENUM(\'success\',\'failed\') NOT NULL DEFAULT \'success\',
        `note`        TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (Throwable $e) {
    error_log('[laporan] Gagal buat tabel log: ' . $e->getMessage());
}

$result = null;
$me     = get_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['datafile'])) {
    $file    = $_FILES['datafile'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tmpPath = $file['tmp_name'];

    $log = [
        'user_id'    => $me['id'],
        'user_name'  => $me['name'],
        'user_email' => $me['email'],
        'user_role'  => $me['role'],
        'file_name'  => $file['name'],
        'file_type'  => $ext,
        'file_size'  => (int)($file['size'] ?? 0),
        'row_count'  => null,
        'status'     => 'failed',
        'note'       => '',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $log['note'] = 'Upload error code: ' . $file['error'];
        $result = ['success' => false, 'message' => 'Upload gagal (kode error: ' . $file['error'] . ')'];
    } elseif (!in_array($ext, ['xlsx', 'csv', 'sql'])) {
        $log['note'] = 'Format tidak didukung: ' . $ext;
        $result = ['success' => false, 'message' => 'Format file tidak didukung. Gunakan .xlsx, .csv, atau .sql'];
    } else {
        try {
            if ($ext === 'sql') {
                $sql_content = file_get_contents($tmpPath);
                if ($sql_content === false) throw new Exception('Tidak bisa membaca file SQL.');
                $pdo->exec($sql_content);
                $log['status'] = 'success';
                $log['note']   = 'Eksekusi SQL langsung';
                $result = ['success' => true, 'message' => 'File SQL berhasil dieksekusi.', 'rows' => null];
            } else {
                $rows = $ext === 'xlsx' ? _read_xlsx($tmpPath) : _read_csv($tmpPath);
                if (empty($rows)) throw new Exception('File kosong atau tidak ada data yang terbaca.');
                $headers = array_map('trim', array_shift($rows));
                if (empty($headers)) throw new Exception('Header kolom tidak ditemukan.');
                $matched = array_intersect($headers, $KNOWN_COLUMNS);
                if (count($matched) < 3) {
                    throw new Exception('Kolom tidak cocok dengan format yang dikenal.');
                }
                _ensure_table($pdo, TARGET_TABLE, $headers);
                $pdo->exec('TRUNCATE TABLE `' . TARGET_TABLE . '`');
                $cols_escaped = array_map(fn($c) => '`' . $c . '`', $headers);
                $placeholders = implode(', ', array_fill(0, count($headers), '?'));
                $stmt = $pdo->prepare(
                    'INSERT INTO `' . TARGET_TABLE . '` (' . implode(', ', $cols_escaped) . ') VALUES (' . $placeholders . ')'
                );
                $pdo->beginTransaction();
                foreach ($rows as $row) {
                    $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                    $row = array_map(fn($v) => ($v === '' ? null : $v), $row);
                    $stmt->execute($row);
                }
                $pdo->commit();
                $log['status']    = 'success';
                $log['row_count'] = count($rows);
                $log['note']      = 'TRUNCATE + INSERT ' . count($rows) . ' baris';
                $result = ['success' => true, 'message' => 'Tabel berhasil diperbarui.', 'rows' => count($rows)];
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $log['note'] = $e->getMessage();
            $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    try {
        $pdo->prepare('INSERT INTO `' . LOG_TABLE . '`
            (user_id, user_name, user_email, user_role, file_name, file_type, file_size, row_count, status, note)
            VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $log['user_id'],   $log['user_name'],  $log['user_email'], $log['user_role'],
                $log['file_name'], $log['file_type'],  $log['file_size'],
                $log['row_count'], $log['status'],     $log['note'],
            ]);
        $result['_log_id'] = $pdo->lastInsertId();
    } catch (Throwable $e) {
        error_log('[laporan] Gagal simpan log: ' . $e->getMessage());
        $result['_log_err'] = $e->getMessage();
    }
}

$per_page_opts = [10, 20, 50];
$per_page_raw  = (int)($_GET['per'] ?? 20);
$per_page      = in_array($per_page_raw, $per_page_opts) ? $per_page_raw : 20;
$cur_page      = max(1, (int)($_GET['p'] ?? 1));

try {
    // Pastikan tabel ada dulu sebelum query
    $tbl_exists = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = '" . LOG_TABLE . "'")->fetchColumn();

    if ($tbl_exists) {
        $total_logs  = (int)$pdo->query('SELECT COUNT(*) FROM `' . LOG_TABLE . '`')->fetchColumn();
    } else {
        $total_logs = 0;
    }

    $total_pages = ($total_logs > 0) ? (int)ceil($total_logs / $per_page) : 1;
    $cur_page    = min($cur_page, $total_pages);
    $offset      = ($cur_page - 1) * $per_page;

    if ($total_logs > 0) {
        $stmt_logs = $pdo->prepare('SELECT * FROM `' . LOG_TABLE . '` ORDER BY uploaded_at DESC LIMIT ? OFFSET ?');
        $stmt_logs->bindValue(1, $per_page, PDO::PARAM_INT);
        $stmt_logs->bindValue(2, $offset,   PDO::PARAM_INT);
        $stmt_logs->execute();
        $logs = $stmt_logs->fetchAll();
    } else {
        $logs = [];
    }
    $log_query_err = null;
} catch (Throwable $e) {
    $logs = []; $total_logs = 0; $total_pages = 1;
    $log_query_err = $e->getMessage();
}

function _page_url(int $p, int $per): string {
    $q = $_GET; $q['p'] = $p; $q['per'] = $per;
    return '?' . http_build_query($q);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">Upload Data
  <div class="page-sub">Impor data ISR ke database — Excel, CSV, atau SQL</div>
</div>

<?php if ($result): ?>
<div class="upload-toast <?= $result['success'] ? 'toast-success' : 'toast-danger' ?>" id="upload-toast" role="alert">
  <div class="toast-icon">
    <i class="ti <?= $result['success'] ? 'ti-circle-check' : 'ti-alert-circle' ?>"></i>
  </div>
  <div class="toast-body">
    <div class="toast-title"><?= $result['success'] ? 'Upload Berhasil' : 'Upload Gagal' ?></div>
    <div class="toast-msg">
      <?= htmlspecialchars($result['message']) ?>
      <?php if ($result['success'] && isset($result['rows'])): ?>
        — <strong><?= number_format($result['rows']) ?> baris</strong> berhasil dimasukkan ke tabel.
      <?php endif; ?>

    </div>
  </div>
  <button class="toast-close" onclick="document.getElementById('upload-toast').style.display='none'" aria-label="Tutup">
    <i class="ti ti-x"></i>
  </button>
</div>
<?php endif; ?>


<div class="upload-grid">

  <div class="card">
    <div class="card-header">
      <i class="ti ti-file-upload" style="margin-right:.4rem;"></i>
      Upload File Data
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="upload-form">
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label for="datafile" style="display:block;margin-bottom:.4rem;font-weight:500;">File</label>
          <input type="file" id="datafile" name="datafile" accept=".xlsx,.csv,.sql" required
                 style="display:block;width:100%;padding:.5rem .75rem;border:.5px solid var(--border-md);border-radius:var(--radius);background:var(--surface);color:inherit;font-size:.9rem;">
          <small style="color:var(--muted);">Format: .xlsx, .csv, .sql &mdash; maks. 32 MB</small>
        </div>

        <div id="upload-progress" style="display:none;margin-bottom:1.25rem;">
          <div style="height:5px;background:var(--bg);border-radius:99px;overflow:hidden;">
            <div id="progress-bar" style="height:100%;width:0;background:var(--blue);transition:width .3s;border-radius:99px;"></div>
          </div>
          <small id="progress-label" style="color:var(--muted);display:block;margin-top:.35rem;">Mengupload…</small>
        </div>

        <button type="submit" class="btn btn-primary" id="upload-btn" style="width:100%;">
          <i class="ti ti-upload"></i>
          Upload &amp; Proses
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <i class="ti ti-history" style="margin-right:.4rem;"></i>
      Riwayat Upload
      <span style="margin-left:auto;font-size:.78rem;color:var(--muted);font-weight:400;">
        <?= number_format($total_logs) ?> entri
      </span>
    </div>
    <?php if (empty($logs)): ?>
    <div class="empty-state" style="padding:2rem;">
      <i class="ti ti-inbox" aria-hidden="true"></i>
      <div>Belum ada riwayat upload.</div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Waktu</th>
            <th>User</th>
            <th>File</th>
            <th>Ukuran</th>
            <th>Baris</th>
            <th>Status</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td class="td-mono" style="white-space:nowrap;">
              <?= date('d/m/Y H:i', strtotime($l['uploaded_at'])) ?>
            </td>
            <td>
              <div style="font-weight:500;font-size:.85rem;"><?= htmlspecialchars($l['user_name']) ?></div>
              <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($l['user_email']) ?></div>
            </td>
            <td style="max-width:180px;">
              <div style="font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($l['file_name']) ?>">
                <?= htmlspecialchars($l['file_name']) ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;"><?= htmlspecialchars($l['file_type']) ?></div>
            </td>
            <td class="td-muted" style="white-space:nowrap;"><?= _fmt_bytes((int)$l['file_size']) ?></td>
            <td style="text-align:right;font-weight:500;">
              <?= $l['row_count'] !== null ? number_format((int)$l['row_count']) : '—' ?>
            </td>
            <td>
              <?php if ($l['status'] === 'success'): ?>
                <span class="badge badge-granted"><i class="ti ti-check"></i> Berhasil</span>
              <?php else: ?>
                <span class="badge badge-broadcast"><i class="ti ti-x"></i> Gagal</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= htmlspecialchars($l['note']) ?>">
              <?= htmlspecialchars($l['note']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Pagination footer -->
    <div class="log-pagination">
      <div class="pag-info">
        <?php
          $from = ($cur_page - 1) * $per_page + 1;
          $to   = min($cur_page * $per_page, $total_logs);
          echo $from . '–' . $to . ' dari ' . number_format($total_logs);
        ?>
      </div>
      <div class="pag-perpage">
        Tampilkan:
        <?php foreach ($per_page_opts as $opt): ?>
          <?php if ($opt === $per_page): ?>
            <span class="pag-per active"><?= $opt ?></span>
          <?php else: ?>
            <a class="pag-per" href="<?= htmlspecialchars(_page_url(1, $opt)) ?>"><?= $opt ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="pag-nav">
        <?php if ($cur_page > 1): ?>
          <a class="pag-btn" href="<?= htmlspecialchars(_page_url(1, $per_page)) ?>" title="Pertama"><i class="ti ti-chevrons-left"></i></a>
          <a class="pag-btn" href="<?= htmlspecialchars(_page_url($cur_page - 1, $per_page)) ?>" title="Sebelumnya"><i class="ti ti-chevron-left"></i></a>
        <?php else: ?>
          <span class="pag-btn disabled"><i class="ti ti-chevrons-left"></i></span>
          <span class="pag-btn disabled"><i class="ti ti-chevron-left"></i></span>
        <?php endif; ?>
        <span class="pag-cur"><?= $cur_page ?> / <?= $total_pages ?></span>
        <?php if ($cur_page < $total_pages): ?>
          <a class="pag-btn" href="<?= htmlspecialchars(_page_url($cur_page + 1, $per_page)) ?>" title="Berikutnya"><i class="ti ti-chevron-right"></i></a>
          <a class="pag-btn" href="<?= htmlspecialchars(_page_url($total_pages, $per_page)) ?>" title="Terakhir"><i class="ti ti-chevrons-right"></i></a>
        <?php else: ?>
          <span class="pag-btn disabled"><i class="ti ti-chevron-right"></i></span>
          <span class="pag-btn disabled"><i class="ti ti-chevrons-right"></i></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /.upload-grid -->

<style>
/* ── Toast notifikasi ─────────────────────────────────────── */
.upload-toast {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  padding: 1rem 1.1rem;
  border-radius: var(--radius-lg);
  border: .5px solid transparent;
  margin-bottom: 1.25rem;
  animation: toast-in .25s ease;
}
.toast-success { background: var(--green-lt); border-color: #6ee7b7; }
.toast-danger  { background: var(--red-lt);   border-color: #fca5a5; }
.toast-icon { font-size: 1.5rem; line-height: 1; flex-shrink: 0; padding-top: .05rem; }
.toast-success .toast-icon { color: var(--green); }
.toast-danger  .toast-icon { color: var(--red); }
.toast-body { flex: 1; min-width: 0; }
.toast-title { font-weight: 600; font-size: .9rem; margin-bottom: .2rem; }
.toast-success .toast-title { color: var(--green); }
.toast-danger  .toast-title { color: var(--red); }
.toast-msg { font-size: .85rem; color: var(--text); line-height: 1.5; }
.toast-close {
  background: none; border: none; cursor: pointer; padding: .15rem .25rem;
  color: var(--muted); font-size: 1rem; flex-shrink: 0;
  border-radius: var(--radius); line-height: 1; margin-top: .05rem;
}
.toast-close:hover { color: var(--text); background: rgba(0,0,0,.06); }
@keyframes toast-in { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

/* ── Layout grid ──────────────────────────────────────────── */
.upload-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
@media (min-width: 900px) {
  .upload-grid {
    flex-direction: row;
    align-items: flex-start;
  }
  .upload-grid .card:first-child { flex: 0 0 320px; }
  .upload-grid .card:last-child  { flex: 1; min-width: 0; }
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Pagination ───────────────────────────────────────────── */
.log-pagination {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: .5rem .75rem;
  padding: .65rem 1rem;
  border-top: .5px solid var(--border);
  font-size: .8rem;
  color: var(--muted);
}
.pag-info { flex: 1; white-space: nowrap; }
.pag-perpage { display: flex; align-items: center; gap: .3rem; }
.pag-per {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 28px; height: 28px; padding: 0 .4rem;
  border-radius: var(--radius); font-size: .8rem; text-decoration: none;
  color: var(--muted); border: .5px solid transparent;
}
.pag-per:hover     { background: var(--bg); color: var(--text); }
.pag-per.active    { background: var(--blue-lt); color: var(--blue); border-color: var(--blue-bd); font-weight: 600; }
.pag-nav { display: flex; align-items: center; gap: .2rem; }
.pag-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border-radius: var(--radius);
  color: var(--muted); text-decoration: none; font-size: .95rem;
  border: .5px solid transparent;
}
.pag-btn:hover      { background: var(--bg); color: var(--text); }
.pag-btn.disabled   { opacity: .35; pointer-events: none; }
.pag-cur {
  padding: 0 .5rem; font-size: .8rem; color: var(--text);
  font-weight: 500; white-space: nowrap;
}
</style>

<script>
document.getElementById('upload-form').addEventListener('submit', function () {
  const btn  = document.getElementById('upload-btn');
  const prog = document.getElementById('upload-progress');
  const bar  = document.getElementById('progress-bar');
  const lbl  = document.getElementById('progress-label');
  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite"></i> Memproses\u2026';
  prog.style.display = 'block';
  let pct = 0;
  const iv = setInterval(() => {
    pct = Math.min(pct + Math.random() * 15, 90);
    bar.style.width = pct + '%';
    lbl.textContent = pct < 50 ? 'Mengupload file\u2026' : 'Memproses data\u2026';
  }, 300);
  window.addEventListener('beforeunload', () => clearInterval(iv));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>