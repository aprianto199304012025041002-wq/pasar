<?php
require_once __DIR__ . '/../config.php';

// ── Security headers ──────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data: https://*.tile.openstreetmap.org; connect-src 'self'; frame-ancestors 'none';");

$user    = get_user();
$current = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    'dashboard' => ['label' => 'Dashboard',  'icon' => 'ti-layout-dashboard'],
    'data'      => ['label' => 'Data ISR',   'icon' => 'ti-table'],
    'peta'      => ['label' => 'Peta',       'icon' => 'ti-map-pin'],
    'laporan'   => ['label' => 'Laporan',    'icon' => 'ti-file-analytics'],
];

$_initials = strtoupper(substr($user['name'], 0, 2));
$_theme    = $_COOKIE['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($_theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($page_title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:opsz,wght@9..40,300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= file_exists(__DIR__ . '/../assets/css/app.css') ? filemtime(__DIR__ . '/../assets/css/app.css') : '0' ?>">
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div>
      <div class="sidebar-logo-mark"><?= strtoupper(APP_NAME) ?></div>
      <div class="sidebar-logo-sub">BALMON JOGJA</div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <?php foreach ($nav_items as $slug => $item): ?>
    <a href="<?= APP_BASE_URL ?>/<?= $slug ?>.php"
       class="nav-item <?= $current === $slug ? 'active' : '' ?>"
       title="<?= htmlspecialchars($item['label']) ?>">
      <i class="ti <?= $item['icon'] ?>"></i>
      <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- User -->
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $_initials ?></div>
    <div style="flex:1;min-width:0">
      <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="sidebar-user-role"><?= strtoupper(htmlspecialchars($user['role'])) ?></div>
    </div>
    <a href="<?= APP_BASE_URL ?>/logout.php" class="sidebar-logout" title="Logout">
      <i class="ti ti-logout"></i>
    </a>
  </div>

  <!-- Collapse btn (desktop) -->
  <button class="sidebar-collapse-btn" id="btn-collapse" aria-label="Collapse sidebar">
    <i class="ti ti-chevron-left" id="collapse-icon"></i>
  </button>

</aside>

<!-- App wrapper -->
<div class="app-wrapper" id="app-wrapper">

  <!-- Topbar -->
  <header class="app-topbar">
    <button class="topbar-hamburger" id="btn-menu" aria-label="Menu">
      <i class="ti ti-menu-2"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? APP_NAME) ?></div>
    <div class="topbar-actions">
      <!-- Theme toggle -->
      <button class="btn" id="btn-theme" title="Ganti tema"
              style="padding:5px 8px;min-height:32px;border-radius:var(--radius-sm)">
        <i class="ti <?= $_theme === 'dark' ? 'ti-sun' : 'ti-moon' ?>" id="theme-icon"></i>
      </button>
      <!-- Avatar + menu -->
      <div class="avatar-wrap" id="avatar-wrap">
        <div class="topbar-avatar" id="btn-avatar" title="<?= htmlspecialchars($user['name']) ?>">
          <?= $_initials ?>
        </div>
        <div class="avatar-menu" id="avatar-menu">
          <div style="padding:9px 14px 6px">
            <div style="font-size:12px;font-weight:600"><?= htmlspecialchars($user['name']) ?></div>
            <div style="font-size:10px;color:var(--muted);font-family:var(--mono)"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
          <div class="avatar-menu-divider"></div>
          <a href="<?= APP_BASE_URL ?>/logout.php" class="avatar-menu-item danger">
            <i class="ti ti-logout"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Main content -->
  <main class="app-main">
