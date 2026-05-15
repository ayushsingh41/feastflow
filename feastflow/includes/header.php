<?php
// includes/header.php
$currentUser = isLoggedIn() ? currentUser() : null;
$cartCount   = (isLoggedIn() && !isAdmin()) ? getCartCount((int)$_SESSION['user_id']) : 0;
$csrf        = generateCsrf();
$pageTitle   = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($pageTitle) ?> | <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Topbar -->
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="ri-menu-line"></i>
    </button>
    <a href="<?= APP_URL ?>/<?= isAdmin() ? 'admin' : 'customer' ?>/dashboard.php" class="brand">
      <span class="brand-icon"><i class="ri-restaurant-fill"></i></span>
      <span class="brand-name">Feast<em>Flow</em></span>
    </a>
  </div>

  <div class="topbar-center">
    <div class="topbar-search" id="globalSearch">
      <i class="ri-search-line"></i>
      <input type="text" placeholder="Search food, categories..." id="searchInput" autocomplete="off">
    </div>
  </div>

  <div class="topbar-right">
    <button class="icon-btn theme-toggle" id="themeToggle" title="Toggle theme">
      <i class="ri-moon-line" id="themeIcon"></i>
    </button>

    <?php if (!isAdmin()): ?>
    <a href="<?= APP_URL ?>/customer/cart.php" class="icon-btn cart-btn">
      <i class="ri-shopping-cart-2-line"></i>
      <?php if ($cartCount > 0): ?>
      <span class="badge-dot"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <div class="user-menu" id="userMenu">
      <button class="user-trigger">
        <div class="user-avatar">
          <?php if ($currentUser && $currentUser['avatar']): ?>
            <img src="<?= APP_URL ?>/assets/uploads/products/<?= $currentUser['avatar'] ?>" alt="">
          <?php else: ?>
            <span><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div class="user-info">
          <span class="user-name"><?= sanitize($_SESSION['user_name'] ?? '') ?></span>
          <span class="user-role"><?= ucfirst($_SESSION['user_role'] ?? '') ?></span>
        </div>
        <i class="ri-arrow-down-s-line"></i>
      </button>
      <div class="user-dropdown">
        <?php if (isAdmin()): ?>
        <a href="<?= APP_URL ?>/admin/dashboard.php"><i class="ri-dashboard-line"></i> Dashboard</a>
        <?php else: ?>
        <a href="<?= APP_URL ?>/customer/profile.php"><i class="ri-user-line"></i> Profile</a>
        <a href="<?= APP_URL ?>/customer/orders.php"><i class="ri-file-list-3-line"></i> My Orders</a>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <a href="<?= APP_URL ?>/auth/logout.php" class="text-danger"><i class="ri-logout-box-line"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <nav class="sidebar-nav">
    <?php if (isAdmin()): ?>
    <div class="nav-section">
      <span class="nav-label">MAIN</span>
      <a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : '' ?>">
        <i class="ri-dashboard-3-line"></i><span>Dashboard</span>
      </a>
      <a href="<?= APP_URL ?>/admin/analytics.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'analytics') !== false ? 'active' : '' ?>">
        <i class="ri-bar-chart-2-line"></i><span>Analytics</span>
      </a>
    </div>
    <div class="nav-section">
      <span class="nav-label">MANAGEMENT</span>
      <a href="<?= APP_URL ?>/admin/products.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'product') !== false ? 'active' : '' ?>">
        <i class="ri-restaurant-2-line"></i><span>Products</span>
      </a>
      <a href="<?= APP_URL ?>/admin/categories.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'categor') !== false ? 'active' : '' ?>">
        <i class="ri-apps-2-line"></i><span>Categories</span>
      </a>
      <a href="<?= APP_URL ?>/admin/orders.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'order') !== false ? 'active' : '' ?>">
        <i class="ri-file-list-3-line"></i><span>Orders</span>
        <?php $pending = getPDO()->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(); if ($pending > 0): ?>
        <span class="nav-badge"><?= $pending ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/admin/users.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'user') !== false ? 'active' : '' ?>">
        <i class="ri-group-line"></i><span>Users</span>
      </a>
      <a href="<?= APP_URL ?>/admin/coupons.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'coupon') !== false ? 'active' : '' ?>">
        <i class="ri-coupon-3-line"></i><span>Coupons</span>
      </a>
    </div>
    <?php else: ?>
    <div class="nav-section">
      <span class="nav-label">MENU</span>
      <a href="<?= APP_URL ?>/customer/dashboard.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : '' ?>">
        <i class="ri-home-4-line"></i><span>Home</span>
      </a>
      <a href="<?= APP_URL ?>/customer/menu.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'menu') !== false ? 'active' : '' ?>">
        <i class="ri-restaurant-2-line"></i><span>Browse Menu</span>
      </a>
      <a href="<?= APP_URL ?>/customer/cart.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'cart') !== false ? 'active' : '' ?>">
        <i class="ri-shopping-cart-2-line"></i><span>My Cart</span>
        <?php if ($cartCount > 0): ?><span class="nav-badge"><?= $cartCount ?></span><?php endif; ?>
      </a>
    </div>
    <div class="nav-section">
      <span class="nav-label">ACCOUNT</span>
      <a href="<?= APP_URL ?>/customer/orders.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'order') !== false ? 'active' : '' ?>">
        <i class="ri-file-list-3-line"></i><span>My Orders</span>
      </a>
      <a href="<?= APP_URL ?>/customer/profile.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : '' ?>">
        <i class="ri-user-settings-line"></i><span>Profile</span>
      </a>
    </div>
    <?php endif; ?>

    <div class="nav-section sidebar-bottom">
      <a href="<?= APP_URL ?>/auth/logout.php" class="nav-item text-danger">
        <i class="ri-logout-box-line"></i><span>Sign Out</span>
      </a>
    </div>
  </nav>
</aside>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Main Wrapper -->
<div class="main-wrapper">
<main class="main-content">
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="toast-container" id="toastContainer">
    <?php foreach ($_SESSION['flash'] as $f): ?>
    <div class="toast toast-<?= $f['type'] ?>" role="alert">
      <i class="ri-<?= $f['type'] === 'success' ? 'check-circle' : ($f['type'] === 'error' ? 'error-warning' : 'information') ?>-line"></i>
      <span><?= sanitize($f['message']) ?></span>
      <button onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
    </div>
    <?php endforeach; unset($_SESSION['flash']); ?>
  </div>
<?php endif; ?>
