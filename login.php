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
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= filemtime(__DIR__ . '/assets/css/app.css') ?>">
</head>
<body>
<div class="login-page">

  <svg style="position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:0" xmlns="http://www.w3.org/2000/svg">
    <pattern id="dots" x="0" y="0" width="28" height="28" patternUnits="userSpaceOnUse">
      <circle cx="2" cy="2" r="1.2" fill="#c7d2fe"/>
    </pattern>
    <rect width="100%" height="100%" fill="url(#dots)"/>
    <g transform="translate(30,30)" stroke="#1D9E75" stroke-width="1.8" fill="none">
      <path d="M0 40 Q20 10 50 0"/><path d="M5 45 Q30 20 60 10"/><path d="M10 50 Q40 28 70 18"/>
      <line x1="30" y1="25" x2="20" y2="50"/><circle cx="20" cy="55" r="5" fill="#1D9E75" stroke="none"/>
    </g>
    <g transform="translate(90vw,50)" stroke="#EF9F27" stroke-width="1.8" fill="none">
      <circle cx="0" cy="0" r="14" stroke-dasharray="4 3"/>
      <circle cx="0" cy="0" r="27" stroke-dasharray="4 4"/>
      <circle cx="0" cy="0" r="40" stroke-dasharray="3 5"/>
      <circle cx="0" cy="0" r="5" fill="#EF9F27" stroke="none"/>
    </g>
    <g transform="translate(0,80vh)" stroke="#D4537E" stroke-width="2" fill="none">
      <path d="M0 20 Q15 0 30 20 Q45 40 60 20 Q75 0 90 20 Q105 40 120 20 Q135 0 150 20 Q165 40 180 20"/>
    </g>
    <g stroke-width="2" fill="none">
      <path d="M10 80 L10 10 L80 10" stroke="#D4537E"/>
      <path d="M10 90vh L10 100vh L80 100vh" stroke="#1D9E75"/>
    </g>
    <text x="45" y="160" font-size="10" fill="#1D9E75" font-family="monospace" font-weight="bold">1.4 GHz</text>
    <text x="30" y="80vh" font-size="10" fill="#378ADD" font-family="monospace" font-weight="bold">SHF</text>
    <circle cx="160" cy="130" r="5" fill="#D4537E" opacity="0.7"/>
    <circle cx="90" cy="70vh" r="5" fill="#1D9E75" opacity="0.7"/>
  </svg>

  <div class="login-card" style="position:relative;z-index:1;background:rgba(255,255,255,0.93);border-color:#c7d2fe">
    <div class="login-logo" style="background:#4F46E5;border-radius:18px">
      <i class="ti ti-satellite"></i>
    </div>
    <div class="login-title" style="font-size:32px;letter-spacing:6px;color:#4F46E5;font-family:Georgia,serif">PASAR</div>
    <div style="font-size:13px;font-weight:600;letter-spacing:2px;color:#1e1b4b;margin-bottom:6px">PETA STASIUN RADIO</div>
    <div class="login-sub" style="font-size:11px;font-weight:500;letter-spacing:0.8px;text-transform:uppercase">
      Balai Monitor Spektrum Frekuensi Radio Kelas I Yogyakarta
    </div>
    <a href="<?= htmlspecialchars($login_url) ?>" class="btn-sso" style="background:#4F46E5">
      <i class="ti ti-login" style="font-size:18px"></i>
      Masuk dengan GUDEG JOGJA SSO
    </a>
    <p style="margin-top:16px;font-size:11px;color:var(--muted)">
      Akses hanya untuk petugas Balmon yang terdaftar
    </p>
  </div>

  <p style="position:relative;z-index:1;font-size:12px;color:#6366f1;margin-top:4px">© 2026 QOS Balmon Jogja Team</p>

</div>
</body>
</html>