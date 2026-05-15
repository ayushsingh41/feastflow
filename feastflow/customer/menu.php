<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$filters = [
    'search'   => $_GET['search']   ?? '',
    'category' => $_GET['category'] ?? '',
    'sort'     => $_GET['sort']     ?? '',
    'veg'      => $_GET['veg']      ?? '',
    'status'   => 'active',
];

$categories = getCategories(true);
$products   = getProducts($filters);
$pageTitle  = 'Browse Menu';
include __DIR__ . '/../includes/header.php';
?>

<script>
window.APP_URL    = '<?= APP_URL ?>';
window.CSRF_TOKEN = '<?= generateCsrf() ?>';
</script>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i>Menu</div>
    <h1 class="page-title">Our <span>Menu</span></h1>
    <p class="page-subtitle"><?= count($products) ?> delicious items available</p>
  </div>
  <a href="<?= APP_URL ?>/customer/cart.php" class="btn btn-primary">
    <i class="ri-shopping-cart-2-line"></i> View Cart
  </a>
</div>

<!-- Search & Sort Bar -->
<form method="GET" action="" id="menuForm">
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
    <div class="search-filter" style="flex:1;min-width:200px">
      <i class="ri-search-line"></i>
      <input type="text" name="search" placeholder="Search food items..." value="<?= sanitize($filters['search']) ?>" style="width:100%">
    </div>
    <select name="sort" class="filter-select" onchange="this.form.submit()">
      <option value="">Sort By</option>
      <option value="popular"    <?= $filters['sort']==='popular'   ?'selected':'' ?>>Most Popular</option>
      <option value="rating"     <?= $filters['sort']==='rating'    ?'selected':'' ?>>Top Rated</option>
      <option value="price_asc"  <?= $filters['sort']==='price_asc' ?'selected':'' ?>>Price: Low → High</option>
      <option value="price_desc" <?= $filters['sort']==='price_desc'?'selected':'' ?>>Price: High → Low</option>
      <option value="newest"     <?= $filters['sort']==='newest'    ?'selected':'' ?>>Newest First</option>
    </select>
    <label style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-size:13px;<?= $filters['veg']?'border-color:var(--green);background:rgba(16,185,129,0.1);color:var(--green)':'' ?>">
      <input type="checkbox" name="veg" value="1" <?= $filters['veg']?'checked':'' ?> onchange="this.form.submit()" style="accent-color:var(--green)">
      🌱 Veg Only
    </label>
    <button type="submit" class="btn btn-outline btn-sm"><i class="ri-search-line"></i> Search</button>
    <?php if ($filters['search'] || $filters['category'] || $filters['sort'] || $filters['veg']): ?>
    <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-outline btn-sm"><i class="ri-refresh-line"></i> Reset</a>
    <?php endif; ?>
    <input type="hidden" name="category" value="<?= sanitize($filters['category']) ?>">
  </div>
</form>

<!-- Category Pills -->
<div class="category-pills">
  <a href="<?= APP_URL ?>/customer/menu.php?<?= http_build_query(array_merge($filters,['category'=>''])) ?>" class="cat-pill <?= empty($filters['category'])?'active':'' ?>">
    <i class="ri-apps-2-line"></i> All Items
  </a>
  <?php foreach ($categories as $c): ?>
  <a href="<?= APP_URL ?>/customer/menu.php?<?= http_build_query(array_merge($filters,['category'=>$c['id']])) ?>" class="cat-pill <?= $filters['category']==$c['id']?'active':'' ?>">
    <i class="<?= htmlspecialchars($c['icon']) ?>"></i> <?= sanitize($c['name']) ?>
    <span style="opacity:0.7;font-size:11px">(<?= $c['product_count'] ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Products Grid -->
<?php if (empty($products)): ?>
<div class="empty-state">
  <i class="ri-restaurant-2-line"></i>
  <h3>No items found</h3>
  <p>Try a different search term or category.</p>
  <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-primary">View All Items</a>
</div>
<?php else: ?>
<div class="products-grid">
  <?php foreach ($products as $p): ?>
  <div class="product-card" data-cat="<?= $p['category_id'] ?>">
    <div class="product-img">
      <?php if ($p['image']): ?>
      <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" loading="lazy">
      <?php else: ?><div class="no-img">🍽️</div><?php endif; ?>
      <div class="product-badges">
        <?php if ($p['is_featured']): ?><span class="product-badge featured">★ Featured</span><?php endif; ?>
        <span class="product-badge <?= $p['is_veg']?'veg':'nonveg' ?>"><?= $p['is_veg']?'🌱 Veg':'🍗' ?></span>
      </div>
    </div>
    <div class="product-body">
      <div class="product-cat"><?= sanitize($p['category_name']) ?></div>
      <div class="product-name"><?= sanitize($p['name']) ?></div>
      <div class="product-desc"><?= sanitize($p['description']) ?></div>
      <div class="product-meta">
        <div class="product-price"><?= formatPrice($p['price']) ?></div>
        <div class="product-rating">
          <i class="ri-star-fill"></i><?= number_format($p['rating'],1) ?>
          <span style="color:var(--text-light)">(<?= $p['total_orders'] ?>)</span>
        </div>
      </div>
    </div>
    <div class="product-footer">
      <button class="btn btn-outline btn-sm" onclick="quickView(<?= $p['id'] ?>)"><i class="ri-eye-line"></i></button>
      <button class="btn btn-primary btn-sm" style="flex:1" onclick="addToCart(<?= $p['id'] ?>, this)">
        <i class="ri-shopping-cart-2-line"></i> Add to Cart
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Quick View Modal -->
<div class="modal-backdrop" id="quickViewModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title" id="qvTitle">Product Details</div>
      <button class="modal-close" onclick="document.getElementById('quickViewModal').classList.remove('open')"><i class="ri-close-line"></i></button>
    </div>
    <div class="modal-body" id="qvBody">
      <div style="text-align:center;padding:20px"><span class="loading" style="width:32px;height:32px"></span></div>
    </div>
  </div>
</div>

<!-- Sticky Cart Bar (when cart has items) -->
<?php
$cartCount = getCartCount((int)$_SESSION['user_id']);
if ($cartCount > 0):
$cart = getCart((int)$_SESSION['user_id']);
$total = getCartTotal($cart);
?>
<div style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:800;animation:slideInRight 0.3s ease">
  <a href="<?= APP_URL ?>/customer/cart.php" class="btn btn-primary btn-lg" style="border-radius:50px;box-shadow:0 8px 24px rgba(245,158,11,0.4);padding:14px 28px">
    <i class="ri-shopping-cart-2-line"></i>
    <?= $cartCount ?> item<?= $cartCount>1?'s':'' ?> in cart ·
    <strong><?= formatPrice($total['total']) ?></strong>
    <i class="ri-arrow-right-line"></i>
  </a>
</div>
<?php endif; ?>

<script>
// Build product data for quick view
const products = <?= json_encode(array_map(fn($p) => [
  'id'   => $p['id'],
  'name' => $p['name'],
  'description' => $p['description'],
  'price' => $p['price'],
  'image' => $p['image'],
  'category_name' => $p['category_name'],
  'rating' => $p['rating'],
  'total_orders' => $p['total_orders'],
  'is_veg' => $p['is_veg'],
], $products)) ?>;

function quickView(id) {
  const p = products.find(x => x.id == id);
  if (!p) return;
  document.getElementById('qvTitle').textContent = p.name;
  const img = p.image
    ? `<img src="${window.APP_URL}/assets/uploads/products/${p.image}" style="width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:16px">`
    : `<div style="height:160px;display:grid;place-items:center;font-size:64px;background:var(--bg-input);border-radius:10px;margin-bottom:16px">🍽️</div>`;
  document.getElementById('qvBody').innerHTML = `
    ${img}
    <div style="font-size:12px;color:var(--amber);font-weight:600;margin-bottom:4px">${p.category_name}</div>
    <div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:8px">${p.name}</div>
    <div style="color:var(--text-muted);font-size:13.5px;margin-bottom:16px;line-height:1.6">${p.description || 'A delicious dish crafted with fresh ingredients.'}</div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div style="font-size:24px;font-weight:700;color:var(--amber)">₹${parseFloat(p.price).toFixed(2)}</div>
      <div style="font-size:13px;color:var(--text-muted)">⭐ ${p.rating} · ${p.total_orders} orders</div>
    </div>
    <div style="display:flex;gap:10px">
      <span style="padding:4px 12px;border-radius:50px;font-size:12px;font-weight:600;background:${p.is_veg?'rgba(16,185,129,0.15)':'rgba(239,68,68,0.15)'};color:${p.is_veg?'var(--green)':'var(--red)'}">${p.is_veg?'🌱 Vegetarian':'🍗 Non-vegetarian'}</span>
    </div>
    <div style="margin-top:20px">
      <button class="btn btn-primary btn-block btn-lg" onclick="addToCart(${p.id}, this);document.getElementById('quickViewModal').classList.remove('open')">
        <i class="ri-shopping-cart-2-line"></i> Add to Cart
      </button>
    </div>
  `;
  document.getElementById('quickViewModal').classList.add('open');
}

// Close modal on backdrop click
document.getElementById('quickViewModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
