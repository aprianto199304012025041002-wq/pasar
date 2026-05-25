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
    background-image:
        radial-gradient(circle, #c7d2fe 1.2px, transparent 1.2px);
    background-size: 28px 28px;
    position: relative;
    overflow: hidden;
}

.login-doodle {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
}

.login-card {
    position: relative;
    z-index: 1;
    background: rgba(255,255,255,0.93);
    border: 0.5px solid #c7d2fe;
    border-radius: 16px;
    padding: 40px 36px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.login-copy {
    position: relative;
    z-index: 1;
    font-size: 12px;
    color: #6366f1;
}
</style>
</head>
<body>
<div class="login-page">

  <svg class="login-doodle" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" viewBox="0 0 1440 900">
    <!-- satellite dish top-left: teal -->
    <g transform="translate(40,40)" stroke="#1D9E75" stroke-width="2" fill="none">
      <path d="M0 50 Q25 12 62 0"/>
      <path d="M6 56 Q37 25 75 12"/>
      <path d="M12 62 Q50 35 88 22"/>
      <line x1="37" y1="31" x2="25" y2="62"/>
      <circle cx="25" cy="68" r="6" fill="#1D9E75" stroke="none"/>
    </g>

    <!-- signal rings top-right: amber -->
    <g transform="translate(1380,60)" stroke="#EF9F27" stroke-width="2" fill="none">
      <circle cx="0" cy="0" r="18" stroke-dasharray="5 4"/>
      <circle cx="0" cy="0" r="34" stroke-dasharray="5 5"/>
      <circle cx="0" cy="0" r="52" stroke-dasharray="4 6"/>
      <circle cx="0" cy="0" r="6" fill="#EF9F27" stroke="none"/>
    </g>

    <!-- wave bottom-left: pink -->
    <g transform="translate(0,800)" stroke="#D4537E" stroke-width="2.5" fill="none">
      <path d="M0 20 Q20 0 40 20 Q60 40 80 20 Q100 0 120 20 Q140 40 160 20 Q180 0 200 20 Q220 40 240 20 Q260 0 280 20"/>
    </g>

    <!-- circuit bottom-right: blue -->
    <g transform="translate(1180,720)" stroke="#378ADD" stroke-width="1.8" fill="none">
      <line x1="0" y1="0" x2="80" y2="0"/>
      <line x1="80" y1="0" x2="80" y2="60"/>
      <line x1="80" y1="60" x2="180" y2="60"/>
      <circle cx="80" cy="0" r="5" fill="#378ADD" stroke="none"/>
      <circle cx="80" cy="60" r="5" fill="#378ADD" stroke="none"/>
      <rect x="92" y="48" width="22" height="22" rx="3" stroke="#185FA5" fill="#E6F1FB"/>
      <line x1="0" y1="30" x2="45" y2="30"/>
      <line x1="45" y1="30" x2="45" y2="90"/>
      <circle cx="45" cy="30" r="5" fill="#85B7EB" stroke="none"/>
    </g>

    <!-- satellite mid-right: coral -->
    <g transform="translate(1340,400)" stroke="#D85A30" stroke-width="1.8" fill="none">
      <rect x="-12" y="-6" width="24" height="12" rx="3"/>
      <line x1="-26" y1="0" x2="-12" y2="0"/>
      <line x1="12" y1="0" x2="26" y2="0"/>
      <rect x="-32" y="-5" width="8" height="10" rx="2" fill="#FAECE7" stroke="#D85A30"/>
      <rect x="24" y="-5" width="8" height="10" rx="2" fill="#FAECE7" stroke="#D85A30"/>
    </g>

    <!-- satellite mid-left: teal -->
    <g transform="translate(80,450)" stroke="#1D9E75" stroke-width="1.8" fill="none">
      <rect x="-12" y="-6" width="24" height="12" rx="3"/>
      <line x1="-26" y1="0" x2="-12" y2="0"/>
      <line x1="12" y1="0" x2="26" y2="0"/>
      <rect x="-32" y="-5" width="8" height="10" rx="2" fill="#E1F5EE" stroke="#1D9E75"/>
      <rect x="24" y="-5" width="8" height="10" rx="2" fill="#E1F5EE" stroke="#1D9E75"/>
    </g>

    <!-- antenna top-center: purple -->
    <g transform="translate(720,20)" stroke="#7F77DD" stroke-width="1.8" fill="none">
      <line x1="0" y1="40" x2="0" y2="5"/>
      <line x1="-24" y1="22" x2="24" y2="22"/>
      <line x1="-16" y1="14" x2="16" y2="14"/>
      <line x1="-8" y1="6" x2="8" y2="6"/>
      <line x1="-24" y1="40" x2="24" y2="40"/>
    </g>

    <!-- frequency labels -->
    <text x="60" y="200" font-size="12" fill="#1D9E75" font-family="monospace" font-weight="bold">1.4 GHz</text>
    <text x="1300" y="200" font-size="12" fill="#7F77DD" font-family="monospace" font-weight="bold">UHF</text>
    <text x="60" y="860" font-size="12" fill="#378ADD" font-family="monospace" font-weight="bold">SHF</text>
    <text x="1300" y="520" font-size="12" fill="#EF9F27" font-family="monospace" font-weight="bold">VHF</text>
    <text x="120" y="740" font-size="12" fill="#D4537E" font-family="monospace" font-weight="bold">ISR</text>
    <text x="1260" y="660" font-size="12" fill="#D85A30" font-family="monospace" font-weight="bold">433 MHz</text>

    <!-- corner brackets multicolor -->
    <path d="M12 100 L12 12 L100 12" stroke="#D4537E" stroke-width="2.5" fill="none"/>
    <path d="M1428 100 L1428 12 L1340 12" stroke="#EF9F27" stroke-width="2.5" fill="none"/>
    <path d="M12 800 L12 888 L100 888" stroke="#1D9E75" stroke-width="2.5" fill="none"/>
    <path d="M1428 800 L1428 888 L1340 888" stroke="#378ADD" stroke-width="2.5" fill="none"/>

    <!-- accent dots -->
    <circle cx="200" cy="160" r="6" fill="#D4537E" opacity="0.7"/>
    <circle cx="1260" cy="260" r="5" fill="#EF9F27" opacity="0.7"/>
    <circle cx="110" cy="600" r="6" fill="#1D9E75" opacity="0.7"/>
    <circle cx="1360" cy="560" r="5" fill="#7F77DD" opacity="0.7"/>
    <circle cx="260" cy="820" r="5" fill="#378ADD" opacity="0.7"/>
    <circle cx="1100" cy="120" r="6" fill="#D85A30" opacity="0.7"/>

    <!-- diagonal dashes -->
    <line x1="160" y1="80" x2="190" y2="110" stroke="#AFA9EC" stroke-width="1.5" stroke-dasharray="4 5"/>
    <line x1="190" y1="80" x2="220" y2="110" stroke="#AFA9EC" stroke-width="1.5" stroke-dasharray="4 5"/>
    <line x1="1220" y1="740" x2="1250" y2="770" stroke="#9FE1CB" stroke-width="1.5" stroke-dasharray="4 5"/>
    <line x1="1250" y1="740" x2="1280" y2="770" stroke="#9FE1CB" stroke-width="1.5" stroke-dasharray="4 5"/>
  </svg>

  <div class="login-card">
    <div class="login-logo" style="background:#4F46E5;border-radius:18px;width:68px;height:68px;font-size:34px;margin:0 auto 20px">
      <i class="ti ti-satellite"></i>
    </div>
    <div style="font-size:32px;font-weight:600;letter-spacing:6px;color:#4F46E5;margin-bottom:4px;font-family:Georgia,serif">PASAR</div>
    <div style="font-size:13px;font-weight:700;letter-spacing:2.5px;color:#1e1b4b;margin-bottom:8px">PETA STASIUN RADIO</div>
    <div style="font-size:11px;font-weight:500;letter-spacing:0.8px;color:var(--muted);margin-bottom:28px;line-height:1.7;text-transform:uppercase">
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

  <p class="login-copy">© 2026 QOS Balmon Jogja Team</p>

</div>
</body>
</html>