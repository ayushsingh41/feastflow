<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$cart   = getCart($userId);

if (empty($cart)) {
    header('Location: '.APP_URL.'/customer/menu.php');
    exit;
}

$user   = currentUser();
$totals = getCartTotal($cart);
$coupon = $_SESSION['coupon'] ?? null;
$discount = $coupon ? $coupon['discount'] : 0;
$finalTotal = max(0, $totals['total'] - $discount);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['_ff_csrf'] ?? '')) {
    if (empty(trim($_POST['delivery_address'] ?? ''))) {
        $error = 'Delivery address is required.';
    } else {
        $orderTotals = [
            'subtotal' => $totals['subtotal'],
            'delivery' => $totals['delivery'],
            'discount' => $discount,
            'total'    => $finalTotal,
        ];
        $orderData = [
            'payment_method'   => $_POST['payment_method'] ?? 'cod',
            'delivery_address' => trim($_POST['delivery_address']),
            'notes'            => trim($_POST['notes'] ?? ''),
        ];

        $orderNum = createOrder($userId, $cart, $orderTotals, $orderData);
        if ($orderNum) {
            unset($_SESSION['coupon']);
            // Update coupon usage
            if ($coupon) {
                getPDO()->prepare("UPDATE coupons SET used_count=used_count+1 WHERE code=?")->execute([$coupon['code']]);
            }
            $_SESSION['flash'][] = ['type'=>'success','message'=>"Order {$orderNum} placed successfully! 🎉"];
            header('Location: '.APP_URL.'/customer/orders.php');
            exit;
        }
        $error = 'Failed to place order. Please try again.';
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i><a href="<?= APP_URL ?>/customer/cart.php">Cart</a><i class="ri-arrow-right-s-line"></i>Checkout</div>
    <h1 class="page-title">Checkout</h1>
  </div>
  <a href="<?= APP_URL ?>/customer/cart.php" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Back to Cart</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><i class="ri-error-warning-line"></i><?= sanitize($error) ?></div><?php endif; ?>

<div class="cart-layout">
  <div>
    <form method="POST" id="checkoutForm">
      <?= csrfField() ?>

      <!-- Delivery Address -->
      <div class="card mb-16">
        <div class="card-header"><div class="card-title"><i class="ri-map-pin-line"></i>Delivery Address</div></div>
        <div class="card-body">
          <?php if ($user['address']): ?>
          <div style="display:flex;gap:10px;margin-bottom:12px">
            <div style="flex:1;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer" onclick="useSavedAddress()" id="savedAddrCard">
              <div style="font-size:12px;color:var(--amber);font-weight:600;margin-bottom:4px">Saved Address</div>
              <div style="font-size:13px"><?= sanitize($user['address']) ?></div>
            </div>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Full Delivery Address <span class="req">*</span></label>
            <textarea name="delivery_address" class="form-control" id="deliveryAddr" rows="3" placeholder="House/Flat no., Street, Area, City, PIN code" required><?= sanitize($_POST['delivery_address'] ?? $user['address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Delivery Instructions (Optional)</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Ring bell twice, Leave at door...">
          </div>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="card mb-16">
        <div class="card-header"><div class="card-title"><i class="ri-bank-card-line"></i>Payment Method</div></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php
            $methods = [
              'cod'  => ['icon'=>'ri-money-rupee-circle-line', 'label'=>'Cash on Delivery', 'sub'=>'Pay when your order arrives'],
              'upi'  => ['icon'=>'ri-smartphone-line',          'label'=>'UPI Payment',       'sub'=>'Google Pay, PhonePe, Paytm'],
              'card' => ['icon'=>'ri-bank-card-2-line',         'label'=>'Debit/Credit Card', 'sub'=>'Visa, Mastercard, RuPay'],
            ];
            $selected = $_POST['payment_method'] ?? 'cod';
            foreach ($methods as $key => $m):
            ?>
            <label style="display:flex;align-items:center;gap:14px;padding:14px;border:1px solid <?= $selected===$key?'var(--amber)':'var(--border)' ?>;border-radius:var(--radius-sm);cursor:pointer;transition:all 0.2s;background:<?= $selected===$key?'var(--amber-glow)':'var(--bg-input)' ?>" class="payment-option">
              <input type="radio" name="payment_method" value="<?= $key ?>" <?= $selected===$key?'checked':'' ?> style="accent-color:var(--amber)" onchange="updatePayStyle()">
              <i class="<?= $m['icon'] ?>" style="font-size:22px;color:var(--amber)"></i>
              <div>
                <div style="font-weight:600;font-size:14px"><?= $m['label'] ?></div>
                <div style="font-size:12px;color:var(--text-muted)"><?= $m['sub'] ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="padding:16px">
        <i class="ri-shield-check-line"></i> Place Order · <?= formatPrice($finalTotal) ?>
      </button>
      <div style="text-align:center;margin-top:10px;font-size:12px;color:var(--text-muted)">
        <i class="ri-lock-line"></i> Your order and payment information are secure.
        <br>By placing order you agree to our <a href="#" style="color:var(--amber)">Terms of Service</a>.
      </div>
    </form>
  </div>

  <!-- Order Summary -->
  <div>
    <div class="order-summary">
      <div class="summary-title">Order Summary</div>
      <div style="margin-bottom:16px;max-height:300px;overflow-y:auto">
        <?php foreach ($cart as $item): ?>
        <div class="cart-item" style="padding:10px 0">
          <div class="cart-item-img" style="width:48px;height:48px">
            <?php if ($item['image']): ?><img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($item['image']) ?>" alt="">
            <?php else: ?><div style="width:100%;height:100%;display:grid;place-items:center;font-size:22px;background:var(--bg-input)">🍽️</div><?php endif; ?>
          </div>
          <div class="cart-item-info">
            <div class="cart-item-name" style="font-size:13px"><?= sanitize($item['name']) ?></div>
            <div class="cart-item-price" style="font-size:12px">×<?= $item['quantity'] ?></div>
          </div>
          <div class="fw-bold" style="font-size:13px"><?= formatPrice($item['subtotal']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($totals['subtotal']) ?></span></div>
      <div class="summary-row"><span>Delivery</span><span><?= $totals['delivery']>0?formatPrice($totals['delivery']):'<span class="text-success">FREE</span>' ?></span></div>
      <?php if ($discount > 0): ?>
      <div class="summary-row" style="color:var(--green)"><span>Coupon (<?= sanitize($coupon['code']) ?>)</span><span>-<?= formatPrice($discount) ?></span></div>
      <?php endif; ?>
      <div class="summary-row total"><span>Total Amount</span><span><?= formatPrice($finalTotal) ?></span></div>

      <?php if ($totals['delivery'] === 0.0): ?>
      <div class="alert alert-success" style="margin-top:12px;padding:8px 12px;font-size:12px"><i class="ri-truck-line"></i>FREE Delivery!</div>
      <?php endif; ?>

      <div style="margin-top:14px;padding:10px;background:var(--bg-input);border-radius:var(--radius-sm)">
        <div style="font-size:11px;color:var(--text-muted);text-align:center;margin-bottom:6px">Estimated delivery time</div>
        <div style="font-size:18px;font-weight:700;text-align:center;color:var(--amber)">30 – 45 minutes</div>
      </div>
    </div>
  </div>
</div>

<script>
function useSavedAddress() {
  const addr = <?= json_encode($user['address'] ?? '') ?>;
  document.getElementById('deliveryAddr').value = addr;
}
function updatePayStyle() {
  document.querySelectorAll('.payment-option').forEach(el => {
    const radio = el.querySelector('input[type=radio]');
    el.style.borderColor = radio.checked ? 'var(--amber)' : 'var(--border)';
    el.style.background  = radio.checked ? 'var(--amber-glow)' : 'var(--bg-input)';
  });
}
document.querySelectorAll('input[name=payment_method]').forEach(r=>r.addEventListener('change',updatePayStyle));
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
