<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/dashboard.php');
    exit;
}
_session_start();
$state = bin2hex(random_bytes(16));
$_SESSION['sso_state']    = $state;
$_SESSION['sso_state_ts'] = time();

setcookie('sso_state', $state, [
    'expires'  => time() + 600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$login_url = SSO_URL . '?client_id=' . urlencode(SSO_CLIENT_ID) . '&state=' . urlencode($state);

$err_msg  = '';
$err_code = $_GET['err'] ?? '';
switch ($err_code) {
    case '1':   $err_msg = 'Token tidak ditemukan. Coba login kembali.'; break;
    case '2':   $err_msg = 'Token tidak valid atau sudah kedaluwarsa.'; break;
    case '403': $err_msg = 'Akun Anda tidak memiliki akses ke aplikasi ini.'; break;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#080b10">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= file_exists(__DIR__ . '/assets/css/app.css') ? filemtime(__DIR__ . '/assets/css/app.css') : '0' ?>">
<style>
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    transition: background var(--t-med), color var(--t-med);
}
body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100dvh;
    padding: 20px 16px;
    flex-direction: column;
    gap: 0;
}
.theme-fab {
    position: fixed;
    top: calc(14px + env(safe-area-inset-top));
    right: 14px;
    z-index: 999;
    width: 36px; height: 36px;
    background: var(--glass-hi);
    backdrop-filter: blur(16px) saturate(160%);
    border: 1px solid var(--border-hi);
    border-radius: var(--radius);
    cursor: pointer;
    color: var(--muted-hi);
    box-shadow: var(--shadow-float);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    transition: border-color var(--t-fast), color var(--t-fast);
    -webkit-tap-highlight-color: transparent;
}
.theme-fab:hover { border-color: var(--accent); color: var(--accent); }
.login-wrap { width: 100%; max-width: 340px; }
.logo-area { text-align: center; margin-bottom: 24px; }
.logo-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    background: var(--accent-bg);
    border: 1px solid var(--accent-bdr);
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
    box-shadow: 0 0 24px var(--accent-glow);
    font-size: 26px; color: var(--accent);
}
.logo-name {
    font-size: 20px; font-weight: 700;
    letter-spacing: 2px;
    color: var(--accent);
    font-family: var(--sans);
    margin-bottom: 6px;
}
.logo-desc {
    font-size: 10px; color: var(--muted-hi);
    letter-spacing: 1px; text-transform: uppercase;
    line-height: 1.7; font-family: var(--sans);
}
.logo-org {
    display: block; margin-top: 3px;
    font-size: 10px; color: var(--muted);
    letter-spacing: 0; text-transform: none;
    line-height: 1.6;
}
.login-card {
    background: var(--glass-hi);
    backdrop-filter: blur(20px) saturate(160%);
    border: 1px solid var(--border-hi);
    border-radius: 12px;
    padding: 24px 20px;
    box-shadow: var(--shadow-float);
}
.err-box {
    background: var(--red-glow);
    border: 1px solid rgba(239,68,68,.25);
    border-radius: var(--radius-sm);
    padding: 10px 14px;
    font-size: 12px; color: var(--red);
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}
.btn-sso {
    display: flex; align-items: center; justify-content: center;
    gap: 10px; width: 100%;
    padding: 11px 16px;
    background: var(--accent-bg);
    border: 1px solid var(--accent-bdr);
    border-radius: var(--radius);
    color: var(--accent);
    font-family: var(--sans);
    font-size: 13px; font-weight: 600;
    text-decoration: none;
    transition: background var(--t-fast), border-color var(--t-fast), box-shadow var(--t-fast), transform var(--t-fast);
    -webkit-tap-highlight-color: transparent;
}
.btn-sso:hover {
    background: var(--accent-bg);
    border-color: var(--accent);
    box-shadow: 0 0 20px var(--accent-glow);
    transform: translateY(-1px);
}
.btn-sso:active { transform: scale(.98); }
.login-note {
    text-align: center;
    font-size: 11px; color: var(--muted);
    margin-top: 14px; font-family: var(--sans);
}
.login-footer {
    text-align: center;
    margin-top: 20px;
    font-size: 10px; color: var(--muted);
    letter-spacing: .5px;
}
@media (max-width: 420px) {
    body { align-items: flex-start; padding-top: calc(70px + env(safe-area-inset-top)); }
    .login-card { padding: 20px 16px; }
}
</style>
</head>
<body>

<button class="theme-fab" onclick="toggleTheme()" title="Ganti tema">
  <i class="ti ti-moon" id="theme-icon"></i>
</button>

<div class="login-wrap">
  <div class="logo-area">
    <div class="logo-icon">
      <i class="ti ti-satellite"></i>
    </div>
    <div class="logo-name"><?= APP_NAME ?></div>
    <div class="logo-desc">
      Panduan Analisa Stasiun Radio
      <span class="logo-org">Balai Monitor Spektrum Frekuensi Radio Kelas I Yogyakarta</span>
    </div>
  </div>

  <div class="login-card">
    <?php if ($err_msg): ?>
    <div class="err-box">
      <i class="ti ti-alert-triangle"></i>
      <?= htmlspecialchars($err_msg) ?>
    </div>
    <?php endif; ?>

    <a href="<?= htmlspecialchars($login_url) ?>" class="btn-sso">
      <i class="ti ti-login" style="font-size:18px"></i>
      Masuk dengan GUDEG JOGJA
    </a>

    <p class="login-note">Akses hanya untuk petugas yang terdaftar</p>
  </div>

  <p class="login-footer">© <?= date('Y') ?> QOS Balmon Jogja Team</p>
</div>

<script>
(function() {
    var K = 'pasar_theme';
    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        try { localStorage.setItem(K, t); } catch(e) {}
        document.cookie = 'theme=' + t + ';path=/;max-age=31536000;SameSite=Lax';
        var icon = document.getElementById('theme-icon');
        if (icon) icon.className = t === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
    }
    function get() {
        try { return localStorage.getItem(K) || 'dark'; } catch(e) { return 'dark'; }
    }
    window.toggleTheme = function() { apply(get() === 'dark' ? 'bright' : 'dark'); };
    apply(get());
})();
</script>
</body>
</html>
