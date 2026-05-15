<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

// AJAX endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!verifyCsrf($_POST['_ff_csrf'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pid = (int)$_POST['product_id'];
        $qty = max(1,(int)($_POST['quantity']??1));
        $ok  = addToCart($userId, $pid, $qty);
        $count = getCartCount($userId);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Added to cart!':'Failed to add.','cart_count'=>$count]);
        exit;
    }

    if ($action === 'update') {
        $ok = updateCartItem($userId, (int)$_POST['product_id'], (int)$_POST['quantity']);
        $cart  = getCart($userId);
        $totals = getCartTotal($cart);
        $count = getCartCount($userId);
        echo json_encode(['success'=>$ok,'message'=>'Cart updated','cart_count'=>$count,'totals'=>$totals]);
        exit;
    }

    if ($action === 'remove') {
        $ok = removeFromCart($userId, (int)$_POST['product_id']);
        $cart  = getCart($userId);
        $totals = getCartTotal($cart);
        $count = getCartCount($userId);
        echo json_encode(['success'=>$ok,'message'=>'Item removed','cart_count'=>$count,'totals'=>$totals]);
        exit;
    }

    if ($action === 'coupon') {
        $cart   = getCart($userId);
        $totals = getCartTotal($cart);
        $result = applyCoupon($_POST['code'] ?? '', $totals['subtotal']);
        if ($result['valid']) {
            $_SESSION['coupon'] = ['code'=>$_POST['code'],'discount'=>$result['discount']];
        }
        echo json_encode(['success'=>$result['valid'],'message'=>$result['message'],'discount'=>$result['discount']??0]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}

$cart   = getCart($userId);
$totals = getCartTotal($cart);
$coupon = $_SESSION['coupon'] ?? null;
$discount = $coupon ? $coupon['discount'] : 0;
$finalTotal = $totals['total'] - $discount;

$pageTitle = 'My Cart';
include __DIR__ . '/../includes/header.php';
?>

<script>
window.APP_URL    = '<?= APP_URL ?>';
window.CSRF_TOKEN = '<?= generateCsrf() ?>';
</script>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i>Cart</div>
    <h1 class="page-title">My <span>Cart</span></h1>
    <p class="page-subtitle"><?= count($cart) ?> item<?= count($cart)!=1?'s':'' ?> in your cart</p>
  </div>
  <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Continue Shopping</a>
</div>

<?php if (empty($cart)): ?>
<div class="empty-state" style="padding:80px 20px">
  <i class="ri-shopping-cart-2-line"></i>
  <h3>Your cart is empty</h3>
  <p>Add some delicious items to get started!</p>
  <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-primary btn-lg"><i class="ri-restaurant-2-line"></i> Browse Menu</a>
</div>

<?php else: ?>
<div class="cart-layout">
  <!-- Cart Items -->
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="ri-shopping-bag-line"></i>Cart Items</div>
        <button class="btn btn-outline btn-sm text-danger" onclick="clearCart()"><i class="ri-delete-bin-line"></i> Clear All</button>
      </div>
      <div class="card-body" id="cartItems">
        <?php foreach ($cart as $item): ?>
        <div class="cart-item" id="cart-item-<?= $item['product_id'] ?>">
          <div class="cart-item-img">
            <?php if ($item['image']): ?>
            <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>">
            <?php else: ?><div style="width:100%;height:100%;display:grid;place-items:center;font-size:26px;background:var(--bg-input)">🍽️</div><?php endif; ?>
          </div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?= sanitize($item['name']) ?></div>
            <div class="cart-item-price"><?= formatPrice($item['price']) ?> each</div>
          </div>
          <div class="qty-control">
            <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, -1, <?= $item['quantity'] ?>)"><i class="ri-subtract-line"></i></button>
            <span class="qty-val" id="qty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span>
            <button class="qty-btn" onclick="changeQty(<?= $item['product_id'] ?>, 1, <?= $item['quantity'] ?>)"><i class="ri-add-line"></i></button>
          </div>
          <div style="min-width:80px;text-align:right">
            <div class="fw-bold text-amber" id="sub-<?= $item['product_id'] ?>"><?= formatPrice($item['subtotal']) ?></div>
          </div>
          <button class="icon-btn" onclick="removeItem(<?= $item['product_id'] ?>)" title="Remove" style="color:var(--red);border-color:rgba(239,68,68,0.3)"><i class="ri-delete-bin-line"></i></button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Delivery Notice -->
    <?php if ($totals['delivery'] === 0.0): ?>
    <div class="alert alert-success" style="margin-top:12px"><i class="ri-truck-line"></i>🎉 You qualify for <strong>FREE delivery!</strong></div>
    <?php else: $remaining = FREE_DELIVERY_ABOVE - $totals['subtotal']; ?>
    <div class="alert alert-info" style="margin-top:12px"><i class="ri-truck-line"></i>Add <?= formatPrice($remaining) ?> more for free delivery!</div>
    <?php endif; ?>
  </div>

  <!-- Order Summary -->
  <div>
    <div class="order-summary">
      <div class="summary-title">Order Summary</div>

      <!-- Coupon -->
      <div style="margin-bottom:14px">
        <div id="couponApplied" style="display:<?= $coupon?'block':'none' ?>">
          <div class="alert alert-success" style="margin-bottom:8px;padding:8px 12px">
            <i class="ri-coupon-3-line"></i>
            <span><?= $coupon?sanitize($coupon['code']):'' ?> applied! -<?= formatPrice($discount) ?></span>
            <button onclick="removeCoupon()" style="margin-left:auto;background:none;border:none;color:var(--green);cursor:pointer"><i class="ri-close-line"></i></button>
          </div>
        </div>
        <div id="couponForm" style="display:<?= $coupon?'none':'flex' ?>;gap:8px">
          <input type="text" id="couponCode" class="form-control" placeholder="Enter coupon code" style="flex:1">
          <button class="btn btn-outline btn-sm" onclick="applyCoupon()"><i class="ri-coupon-3-line"></i></button>
        </div>
        <div style="margin-top:6px;font-size:11px;color:var(--text-muted)">Try: FEAST20, NEWUSER50, SAVE100</div>
      </div>

      <div class="summary-row"><span>Subtotal</span><span id="summSubtotal"><?= formatPrice($totals['subtotal']) ?></span></div>
      <div class="summary-row"><span>Delivery Fee</span><span id="summDelivery"><?= $totals['delivery']>0?formatPrice($totals['delivery']):'<span class="text-success">FREE</span>' ?></span></div>
      <?php if ($discount > 0): ?>
      <div class="summary-row" style="color:var(--green)"><span>Coupon Discount</span><span>-<?= formatPrice($discount) ?></span></div>
      <?php endif; ?>
      <div class="summary-row total"><span>Total</span><span id="summTotal"><?= formatPrice($finalTotal) ?></span></div>

      <a href="<?= APP_URL ?>/customer/checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:16px">
        <i class="ri-secure-payment-line"></i> Proceed to Checkout
      </a>

      <div style="margin-top:12px;text-align:center;font-size:12px;color:var(--text-muted)">
        <i class="ri-shield-check-line"></i> Secure checkout · Free cancellation before confirmation
      </div>
    </div>

    <!-- Offers Card -->
    <div class="card" style="margin-top:14px">
      <div class="card-header"><div class="card-title"><i class="ri-coupon-3-line"></i>Available Offers</div></div>
      <div class="card-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
        <?php
        $coupons = getPDO()->query("SELECT code,discount,discount_type,min_order FROM coupons WHERE status='active' LIMIT 3")->fetchAll();
        foreach ($coupons as $cp):
        ?>
        <div style="border:1px dashed var(--border);border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:10px">
          <div style="font-family:var(--font-display);font-weight:700;color:var(--amber);font-size:14px;min-width:90px"><?= sanitize($cp['code']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);flex:1">
            <?= $cp['discount_type']==='percent' ? $cp['discount'].'% off' : '₹'.$cp['discount'].' off' ?>
            · Min ₹<?= $cp['min_order'] ?>
          </div>
          <button class="btn btn-outline btn-sm" onclick="document.getElementById('couponCode').value='<?= sanitize($cp['code']) ?>';applyCoupon()" style="font-size:11px">Apply</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
window.APP_URL    = '<?= APP_URL ?>';
window.CSRF_TOKEN = '<?= generateCsrf() ?>';

function changeQty(pid, delta, current) {
  const newQty = current + delta;
  if (newQty < 1) { removeItem(pid); return; }

  fetch(`${window.APP_URL}/customer/cart.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=update&product_id=${pid}&quantity=${newQty}&_ff_csrf=${window.CSRF_TOKEN}`
  }).then(r=>r.json()).then(d=>{
    if (d.success) location.reload();
    else showToast(d.message,'error');
  });
}

function removeItem(pid) {
  fetch(`${window.APP_URL}/customer/cart.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=remove&product_id=${pid}&_ff_csrf=${window.CSRF_TOKEN}`
  }).then(r=>r.json()).then(d=>{
    if (d.success) location.reload();
    else showToast(d.message,'error');
  });
}

function clearCart() {
  if (!confirm('Remove all items from cart?')) return;
  // Remove each item
  const promises = <?= json_encode(array_column($cart??[],'product_id')) ?>.map(pid =>
    fetch(`${window.APP_URL}/customer/cart.php`, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`action=remove&product_id=${pid}&_ff_csrf=${window.CSRF_TOKEN}`
    })
  );
  Promise.all(promises).then(()=>location.reload());
}

function applyCoupon() {
  const code = document.getElementById('couponCode')?.value;
  if (!code) { showToast('Enter a coupon code','warning'); return; }
  fetch(`${window.APP_URL}/customer/cart.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=coupon&code=${encodeURIComponent(code)}&_ff_csrf=${window.CSRF_TOKEN}`
  }).then(r=>r.json()).then(d=>{
    showToast(d.message, d.success?'success':'error');
    if (d.success) location.reload();
  });
}

function removeCoupon() {
  fetch(`${window.APP_URL}/customer/cart.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=coupon&code=REMOVE&_ff_csrf=${window.CSRF_TOKEN}`
  }).then(()=>{ sessionStorage.removeItem('coupon'); location.reload(); });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
