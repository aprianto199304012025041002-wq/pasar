<?php
require_once __DIR__ . '/../config.php';
$user    = get_user();
$current = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    'dashboard' => ['label' => 'Dashboard',   'icon' => 'ti-layout-dashboard'],
    'data'      => ['label' => 'Data ISR',     'icon' => 'ti-table'],
    'peta'      => ['label' => 'Peta',         'icon' => 'ti-map-pin'],
    'laporan'   => ['label' => 'Laporan',      'icon' => 'ti-file-analytics'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($page_title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
</head>
<body>
<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="ti ti-radio"></i></div>
    <div class="logo-text">
      <div class="logo-name"><?= APP_NAME ?></div>
      <div class="logo-sub"><?= APP_SUBTITLE ?></div>
    </div>
  </div>
  <nav class="sidebar-nav">
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
    <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
    <div class="sidebar-user-info">
      <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
    </div>
    <a href="<?= APP_BASE_URL ?>/logout.php" class="btn-logout" title="Logout">
      <i class="ti ti-logout"></i>
    </a>
  </div>
  <!-- Tombol collapse (desktop only, dikontrol via app.js) -->
  <button class="btn-collapse" id="btn-collapse" aria-label="Collapse sidebar">
    <i class="ti ti-chevron-left" id="collapse-icon"></i>
  </button>
</aside>
<!-- Main wrapper -->
<div class="app-wrapper" id="app-wrapper">
  <!-- Topbar (mobile) -->
  <header class="topbar">
    <button class="btn-menu" id="btn-menu" aria-label="Menu">
      <i class="ti ti-menu-2" id="menu-icon"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? APP_NAME) ?></div>
    <div class="topbar-user">
      <div class="avatar sm"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
    </div>
  </header>
  <main class="app-main">