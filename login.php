<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}
// Generate state CSRF — supaya login via tombol di halaman ini
// juga terlindungi, sama seperti require_login() di auth.php
_session_start(); // pakai helper dari auth.php — konsisten cookie params
$state = bin2hex(random_bytes(16));
$_SESSION['sso_state']    = $state;
$_SESSION['sso_state_ts'] = time();
$login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID) . '&state=' . urlencode($state);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= filemtime(__DIR__ . '/assets/css/app.css') ?>">
<style>
.login-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    gap: 16px;
    background-color: #EEF2FF;
    background-image: radial-gradient(circle, #c7d2fe 1.2px, transparent 1.2px);
    background-size: 28px 28px;
}
.login-card {
    background: rgba(255,255,255,0.93);
    border: 0.5px solid #c7d2fe;
    border-radius: 16px;
    padding: 40px 36px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.login-logo {
    background: #4F46E5;
    border-radius: 18px;
    width: 68px;
    height: 68px;
    font-size: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    margin: 0 auto 20px;
}
</style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo"><i class="ti ti-satellite"></i></div>
    <div style="font-size:32px;font-weight:600;letter-spacing:6px;color:#4F46E5;margin-bottom:4px;font-family:Georgia,serif">PASAR</div>
    <div style="font-size:13px;font-weight:700;letter-spacing:2.5px;color:#1e1b4b;margin-bottom:8px">PETA STASIUN RADIO</div>
    <div style="font-size:11px;font-weight:500;letter-spacing:0.8px;color:var(--muted);margin-bottom:28px;line-height:1.7;text-transform:uppercase">
      Balai Monitor Spektrum Frekuensi Radio<br>Kelas I Yogyakarta
    </div>
    <a href="<?= htmlspecialchars($login_url) ?>" class="btn-sso" style="background:#4F46E5">
      <i class="ti ti-login" style="font-size:18px"></i>
      Masuk dengan GUDEG JOGJA
    </a>
    <p style="margin-top:16px;font-size:11px;color:var(--muted)">
      Akses hanya untuk petugas yang terdaftar
    </p>
  </div>
  <p style="font-size:12px;color:#6366f1">© 2026 QOS Balmon Jogja Team</p>
</div>
</body>
</html>

