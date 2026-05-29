<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$page_title = 'Laporan';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">Laporan
  <div class="page-sub">Halaman ini sedang dalam pengembangan</div>
</div>

<div class="empty-state">
  <i class="ti ti-file-analytics" aria-hidden="true"></i>
  <div>Belum ada laporan yang tersedia.</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>