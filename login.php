<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}
$login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo"><i class="ti ti-radio"></i></div>
    <div class="login-title"><?= APP_NAME ?></div>
    <div class="login-sub">
      Sistem Pemetaan Data ISR<br>
      <?= APP_SUBTITLE ?>
    </div>
    <a href="<?= htmlspecialchars($login_url) ?>" class="btn-sso">
      <i class="ti ti-login" style="font-size:18px"></i>
      Masuk dengan GUDEG JOGJA SSO
    </a>
    <p style="margin-top:20px;font-size:11px;color:var(--muted)">
      Akses hanya untuk petugas Balmon yang terdaftar
    </p>
  </div>
</div>
</body>
</html>
