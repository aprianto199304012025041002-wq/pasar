<?php
require_once __DIR__ . '/../config.php';
$user    = get_user();
$current = basename($_SERVER['PHP_SELF'], '.php');

$nav_items = [
    'dashboard' => ['label' => 'Dashboard',   'icon' => 'ti-dashboard'],
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
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/app.css">
</head>
<body>

<header class="app-header">
  <div class="header-inner">

    <!-- Logo -->
    <div class="logo">
      <div class="logo-icon"><i class="ti ti-radio"></i></div>
      <div>
        <div class="logo-name"><?= APP_NAME ?></div>
        <div class="logo-sub"><?= APP_SUBTITLE ?></div>
      </div>
    </div>

    <!-- Desktop nav (hidden on mobile, shown ≥768px) -->
    <nav class="main-nav" id="main-nav">
      <?php foreach ($nav_items as $slug => $item): ?>
        <a href="<?= APP_BASE_URL ?>/<?= $slug ?>.php"
           class="nav-link <?= $current === $slug ? 'active' : '' ?>">
          <i class="ti <?= $item['icon'] ?>"></i>
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
      <!-- User info inside mobile drawer -->
      <div class="nav-user-mobile">
        <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
        </div>
        <a href="<?= APP_BASE_URL ?>/logout.php" class="btn-logout" title="Logout" style="margin-left:auto">
          <i class="ti ti-logout"></i>
        </a>
      </div>
    </nav>

    <!-- Desktop user (hidden on mobile) -->
    <div class="header-user">
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
      </div>
      <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
      <a href="<?= APP_BASE_URL ?>/logout.php" class="btn-logout" title="Logout">
        <i class="ti ti-logout"></i>
      </a>
    </div>

    <!-- Hamburger (mobile only) -->
    <button class="btn-menu" id="btn-menu" aria-label="Menu">
      <i class="ti ti-menu-2" id="menu-icon"></i>
    </button>

  </div>
</header>

<!-- Bottom Nav (mobile only) -->
<nav class="bottom-nav">
  <?php foreach ($nav_items as $slug => $item): ?>
  <a href="<?= APP_BASE_URL ?>/<?= $slug ?>.php"
     class="bottom-nav-item <?= $current === $slug ? 'active' : '' ?>">
    <i class="ti <?= $item['icon'] ?>"></i>
    <span><?= $item['label'] ?></span>
  </a>
  <?php endforeach; ?>
</nav>

<!-- Backdrop for mobile drawer -->
<div class="nav-backdrop" id="nav-backdrop"></div>

<main class="app-main">