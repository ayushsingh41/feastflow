<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$featured   = getProducts(['featured' => true, 'limit' => 6]);
$categories = getCategories(true);
$popular    = getProducts(['sort' => 'popular', 'limit' => 8]);
$recentOrders = getOrders(['user_id' => $_SESSION['user_id'], 'limit' => 3]);

$pageTitle = 'Home';
include __DIR__ . '/../includes/header.php';
?>

<script>
window.APP_URL    = '<?= APP_URL ?>';
window.CSRF_TOKEN = '<?= generateCsrf() ?>';
</script>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <div>
    <div class="welcome-title">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <span><?= sanitize(explode(' ', $_SESSION['user_name'])[0]) ?>!</span></div>
    <div class="welcome-sub">What are you craving today? Browse our menu and order in minutes.</div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-primary"><i class="ri-restaurant-2-line"></i> Browse Menu</a>
      <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.3);color:rgba(255,255,255,0.8)"><i class="ri-file-list-3-line"></i> My Orders</a>
    </div>
  </div>
</div>

<!-- Quick Stats -->
<?php
$pdo       = getPDO();
$totalOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $totalOrders->execute([$_SESSION['user_id']]); $totalOrders = $totalOrders->fetchColumn();
$totalSpent  = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND status='delivered'"); $totalSpent->execute([$_SESSION['user_id']]); $totalSpent = $totalSpent->fetchColumn();
$cartCount2  = getCartCount((int)$_SESSION['user_id']);
?>
<div class="stats-grid mb-24">
  <div class="stat-card" style="--accent-color:var(--amber)">
    <div class="stat-icon amber"><i class="ri-file-list-3-line"></i></div>
    <div class="stat-info"><div class="stat-label">Total Orders</div><div class="stat-value" data-target="<?= $totalOrders ?>"><?= $totalOrders ?></div></div>
  </div>
  <div class="stat-card" style="--accent-color:var(--green)">
    <div class="stat-icon green"><i class="ri-money-rupee-circle-line"></i></div>
    <div class="stat-info"><div class="stat-label">Total Spent</div><div class="stat-value"><?= formatPrice((float)$totalSpent) ?></div></div>
  </div>
  <div class="stat-card" style="--accent-color:var(--blue)">
    <div class="stat-icon blue"><i class="ri-shopping-cart-2-line"></i></div>
    <div class="stat-info"><div class="stat-label">Cart Items</div><div class="stat-value" data-target="<?= $cartCount2 ?>"><?= $cartCount2 ?></div>
    <div class="stat-change"><a href="<?= APP_URL ?>/customer/cart.php" class="text-amber">View Cart →</a></div></div>
  </div>
  <div class="stat-card" style="--accent-color:var(--purple)">
    <div class="stat-icon purple"><i class="ri-coupon-3-line"></i></div>
    <div class="stat-info"><div class="stat-label">Available Coupons</div><div class="stat-value">3</div><div class="stat-change text-muted">Use at checkout</div></div>
  </div>
</div>

<!-- Categories -->
<div class="mb-24">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700">Browse <span class="text-amber">Categories</span></h2>
    <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px">
    <?php foreach ($categories as $c): ?>
    <a href="<?= APP_URL ?>/customer/menu.php?category=<?= $c['id'] ?>" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 10px;text-align:center;transition:all 0.2s;display:block">
      <div style="width:48px;height:48px;border-radius:14px;background:<?= htmlspecialchars($c['color']) ?>22;display:grid;place-items:center;margin:0 auto 8px;font-size:24px;color:<?= htmlspecialchars($c['color']) ?>">
        <i class="<?= htmlspecialchars($c['icon']) ?>"></i>
      </div>
      <div style="font-size:12px;font-weight:600"><?= sanitize($c['name']) ?></div>
      <div style="font-size:11px;color:var(--text-muted)"><?= $c['product_count'] ?> items</div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Featured Items -->
<?php if (!empty($featured)): ?>
<div class="mb-24">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700">⭐ Featured <span class="text-amber">Items</span></h2>
    <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-outline btn-sm">See All</a>
  </div>
  <div class="products-grid">
    <?php foreach ($featured as $p): ?>
    <div class="product-card" data-cat="<?= $p['category_id'] ?>">
      <div class="product-img">
        <?php if ($p['image']): ?>
        <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" loading="lazy">
        <?php else: ?><div class="no-img">🍽️</div><?php endif; ?>
        <div class="product-badges">
          <span class="product-badge featured">★ Featured</span>
          <span class="product-badge <?= $p['is_veg']?'veg':'nonveg' ?>"><?= $p['is_veg']?'Veg':'Non-veg' ?></span>
        </div>
      </div>
      <div class="product-body">
        <div class="product-cat"><?= sanitize($p['category_name']) ?></div>
        <div class="product-name"><?= sanitize($p['name']) ?></div>
        <div class="product-desc"><?= sanitize($p['description']) ?></div>
        <div class="product-meta">
          <div class="product-price"><?= formatPrice($p['price']) ?></div>
          <div class="product-rating"><i class="ri-star-fill"></i><?= $p['rating'] ?> (<?= $p['total_orders'] ?>)</div>
        </div>
      </div>
      <div class="product-footer">
        <button class="btn btn-primary btn-sm" onclick="addToCart(<?= $p['id'] ?>, this)"><i class="ri-shopping-cart-2-line"></i> Add to Cart</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Popular Items -->
<div class="mb-24">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700">🔥 Most <span class="text-amber">Popular</span></h2>
    <a href="<?= APP_URL ?>/customer/menu.php?sort=popular" class="btn btn-outline btn-sm">See All</a>
  </div>
  <div class="products-grid">
    <?php foreach ($popular as $p): ?>
    <div class="product-card" data-cat="<?= $p['category_id'] ?>">
      <div class="product-img">
        <?php if ($p['image']): ?>
        <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" loading="lazy">
        <?php else: ?><div class="no-img">🍽️</div><?php endif; ?>
        <div class="product-badges">
          <span class="product-badge <?= $p['is_veg']?'veg':'nonveg' ?>"><?= $p['is_veg']?'🌱 Veg':'🍗 Non-veg' ?></span>
        </div>
      </div>
      <div class="product-body">
        <div class="product-cat"><?= sanitize($p['category_name']) ?></div>
        <div class="product-name"><?= sanitize($p['name']) ?></div>
        <div class="product-desc"><?= sanitize($p['description']) ?></div>
        <div class="product-meta">
          <div class="product-price"><?= formatPrice($p['price']) ?></div>
          <div class="product-rating"><i class="ri-star-fill"></i><?= $p['rating'] ?> (<?= $p['total_orders'] ?>)</div>
        </div>
      </div>
      <div class="product-footer">
        <button class="btn btn-primary btn-sm" onclick="addToCart(<?= $p['id'] ?>, this)"><i class="ri-shopping-cart-2-line"></i> Add to Cart</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent Orders -->
<?php if (!empty($recentOrders)): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-history-line"></i>Recent Orders</div>
    <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="card-body" style="padding:0">
    <?php foreach ($recentOrders as $o): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px">
      <div>
        <div class="fw-bold text-amber"><?= sanitize($o['order_number']) ?></div>
        <div class="text-sm text-muted"><?= $o['item_count'] ?> item(s) · <?= timeAgo($o['created_at']) ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:12px">
        <?= statusBadge($o['status']) ?>
        <div class="fw-bold"><?= formatPrice($o['total']) ?></div>
        <a href="<?= APP_URL ?>/customer/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm"><i class="ri-eye-line"></i> View</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
