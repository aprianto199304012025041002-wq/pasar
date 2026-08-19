<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}
// Generate state CSRF
_session_start();
$state = bin2hex(random_bytes(16));
$_SESSION['sso_state']    = $state;
$_SESSION['sso_state_ts'] = time();

// Simpan state juga di cookie terpisah — fallback untuk WebView Android
// yang tidak membawa session cookie saat cross-site redirect chain
// (gudeg.xo.je -> pasar.balmonjogja.info/callback.php).
// SameSite=Lax cukup karena callback adalah top-level GET redirect.
setcookie('sso_state', $state, [
    'expires'  => time() + 600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID) . '&state=' . urlencode($state);
$_theme = $_COOKIE['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($_theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Login — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:opsz,wght@9..40,300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= file_exists(__DIR__ . '/assets/css/app.css') ? filemtime(__DIR__ . '/assets/css/app.css') : '0' ?>">
</head>
<body>

<!-- Theme toggle (pojok kanan atas) -->
<button id="btn-theme" title="Ganti tema" style="
    position:fixed;top:16px;right:16px;z-index:100;
    width:36px;height:36px;border-radius:50%;
    background:var(--surface);border:1px solid var(--border-hi);
    color:var(--muted-hi);font-size:16px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    box-shadow:var(--shadow-float);transition:color .15s,box-shadow .15s;
">
  <i class="ti <?= $_theme === 'dark' ? 'ti-sun' : 'ti-moon' ?>" id="theme-icon"></i>
</button>

<div class="login-page">

  <!-- Grid noise background -->
  <div style="position:fixed;inset:0;pointer-events:none;
    background-image:radial-gradient(circle, var(--border-md) 1px, transparent 1px);
    background-size:28px 28px;opacity:.5"></div>

  <!-- Glow accent -->
  <div style="position:fixed;top:30%;left:50%;transform:translate(-50%,-50%);
    width:500px;height:300px;pointer-events:none;
    background:radial-gradient(ellipse,var(--accent-glow),transparent 70%);
    filter:blur(40px)"></div>

  <div class="login-box">
    <!-- Icon -->
    <div style="width:56px;height:56px;border-radius:50%;
        background:linear-gradient(135deg,var(--accent),#7dd3fc);
        display:flex;align-items:center;justify-content:center;
        margin:0 auto 20px;font-size:26px;color:#000;
        box-shadow:0 0 32px var(--accent-glow)">
      <i class="ti ti-satellite"></i>
    </div>

    <div class="login-logo">PASAR</div>
    <div style="font-size:10px;letter-spacing:2px;color:var(--muted-hi);
        text-transform:uppercase;margin-bottom:6px;font-family:var(--mono)">
      Panduan Analisa Stasiun Radio
    </div>
    <div style="font-size:11px;color:var(--muted);margin-bottom:28px;line-height:1.7">
      Balai Monitor Spektrum Frekuensi Radio<br>Kelas I Yogyakarta
    </div>

    <a href="<?= htmlspecialchars($login_url) ?>" class="login-btn-sso">
      <i class="ti ti-login" style="font-size:18px"></i>
      Masuk dengan GUDEG JOGJA
    </a>

    <p class="login-note">Akses hanya untuk petugas yang terdaftar</p>
  </div>

  <p style="font-size:11px;color:var(--muted);font-family:var(--mono);letter-spacing:.5px">
    © 2026 QOS Balmon Jogja Team
  </p>
</div>

<script>
(function() {
    var btn = document.getElementById('btn-theme');
    var ico = document.getElementById('theme-icon');
    var html = document.documentElement;
    if (btn) btn.addEventListener('click', function() {
        var cur = html.getAttribute('data-theme') || 'dark';
        var next = cur === 'dark' ? 'bright' : 'dark';
        html.setAttribute('data-theme', next);
        ico.className = next === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
        document.cookie = 'theme=' + next + ';path=/;max-age=31536000;SameSite=Lax';
    });
})();
</script>
</body>
</html>
