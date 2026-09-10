<?php
require_once __DIR__ . '/../config.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https://*.tile.openstreetmap.org; connect-src 'self'; frame-ancestors 'none';");

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
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>PASAR — <?= htmlspecialchars($page_title ?? 'Dashboard') ?></title>
<!-- fonts: system stack, no external dependency -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= file_exists(__DIR__ . '/../assets/css/app.css') ? filemtime(__DIR__ . '/../assets/css/app.css') : '0' ?>">
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Sidebar — di LUAR .app-wrapper, sama seperti struktur asli -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="ti ti-satellite"></i></div>
    <div class="logo-text">
      <div class="logo-name"><?= strtoupper(APP_NAME) ?></div>
      <div class="logo-sub">BALMON JOGJA</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Menu</div>
    <?php foreach ($nav_items as $slug => $item): ?>
    <a href="<?= APP_BASE_URL ?>/<?= $slug ?>.php"
       class="sidebar-link <?= $current === $slug ? 'active' : '' ?>"
       title="<?= htmlspecialchars($item['label']) ?>">
      <i class="ti <?= $item['icon'] ?>"></i>
      <span class="nav-label"><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-user">
    <div class="avatar" style="flex-shrink:0"><?= $_initials ?></div>
    <div class="sidebar-user-info">
      <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="user-role"><?= strtoupper(htmlspecialchars($user['role'])) ?></div>
    </div>
    <a href="<?= APP_BASE_URL ?>/logout.php" class="btn-logout" title="Logout" style="flex-shrink:0">
      <i class="ti ti-logout"></i>
    </a>
  </div>

  <button class="btn-collapse" id="btn-collapse" aria-label="Collapse sidebar">
    <i class="ti ti-chevron-left" id="collapse-icon"></i>
  </button>
</aside>

<!-- Main wrapper — sama struktur asli -->
<div class="app-wrapper" id="app-wrapper">

  <header class="topbar">
    <button class="btn-menu" id="btn-menu" aria-label="Menu">
      <i class="ti ti-menu-2" id="menu-icon"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? APP_NAME) ?></div>
    <div class="topbar-user">
      <!-- Theme toggle -->
      <button id="btn-theme" title="Ganti tema" class="btn-theme-toggle">
        <i class="ti <?= $_theme === 'dark' ? 'ti-sun' : 'ti-moon' ?>" id="theme-icon"></i>
      </button>
      <!-- Avatar + dropdown -->
      <div class="avatar-wrap" id="avatar-wrap">
        <div class="avatar sm" id="btn-avatar" title="<?= htmlspecialchars($user['name']) ?>">
          <?= $_initials ?>
        </div>
        <div class="avatar-menu" id="avatar-menu">
          <div class="avatar-menu-header">
            <div class="avatar-menu-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="avatar-menu-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
          <div class="avatar-menu-divider"></div>
          <a href="<?= APP_BASE_URL ?>/logout.php" class="avatar-menu-item danger">
            <i class="ti ti-logout"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="app-main">
