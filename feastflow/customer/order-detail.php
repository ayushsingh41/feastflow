<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$order  = getOrderById((int)($_GET['id']??0), $userId);
if (!$order) { header('Location: '.APP_URL.'/customer/orders.php'); exit; }

// Reorder
if (isset($_GET['reorder'])) {
    foreach ($order['items'] as $item) {
        addToCart($userId, $item['product_id'], $item['quantity']);
    }
    $_SESSION['flash'][] = ['type'=>'success','message'=>'Items added to cart!'];
    header('Location: '.APP_URL.'/customer/cart.php'); exit;
}

$steps = ['pending','confirmed','preparing','out_for_delivery','delivered'];
$currentIdx = array_search($order['status'], $steps);
$pageTitle = 'Order ' . $order['order_number'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i><a href="<?= APP_URL ?>/customer/orders.php">My Orders</a><i class="ri-arrow-right-s-line"></i><?= sanitize($order['order_number']) ?></div>
    <h1 class="page-title">Order <span>#<?= sanitize($order['order_number']) ?></span></h1>
    <div style="margin-top:4px"><?= statusBadge($order['status']) ?></div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="?id=<?= $order['id'] ?>&reorder=1" class="btn btn-outline" data-confirm="Add all items to cart?"><i class="ri-refresh-line"></i> Reorder</a>
    <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Back</a>
  </div>
</div>

<!-- Status Progress -->
<?php if ($order['status'] !== 'cancelled'): ?>
<div class="card mb-16">
  <div class="card-body">
    <div style="overflow-x:auto">
      <div style="display:flex;align-items:flex-start;min-width:600px;padding:10px 0">
        <?php foreach ($steps as $i => $step): ?>
        <?php $done = $currentIdx !== false && $i <= $currentIdx; $isCurrent = $currentIdx === $i; ?>
        <div style="flex:1;text-align:center;position:relative">
          <?php if ($i < count($steps)-1): ?>
          <div style="position:absolute;top:20px;left:50%;right:-50%;height:3px;background:<?= ($currentIdx!==false&&$i<$currentIdx)?'var(--amber)':'var(--border)' ?>;z-index:0;transition:background 0.3s"></div>
          <?php endif; ?>
          <div style="width:40px;height:40px;border-radius:50%;background:<?= $done?'var(--amber)':'var(--bg-input)' ?>;border:2px solid <?= $done?'var(--amber)':'var(--border)' ?>;display:inline-grid;place-items:center;position:relative;z-index:1;font-size:16px;color:<?= $done?'#000':'var(--text-muted)' ?>;<?= $isCurrent?'box-shadow:0 0 0 4px var(--amber-glow)':'' ?>">
            <?php
            $icons = ['ri-time-line','ri-check-line','ri-fire-line','ri-bike-line','ri-home-4-line'];
            echo $done ? '<i class="ri-check-line"></i>' : '<i class="'.$icons[$i].'"></i>';
            ?>
          </div>
          <div style="font-size:11px;margin-top:8px;color:<?= $done?'var(--amber)':'var(--text-muted)' ?>;font-weight:<?= $done?'600':'400' ?>">
            <?= ucwords(str_replace('_',' ',$step)) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if (in_array($order['status'],['pending','confirmed','preparing'])): ?>
    <div style="text-align:center;margin-top:10px;font-size:13px;color:var(--text-muted)">
      <i class="ri-timer-line"></i> Estimated delivery: <strong>30–45 minutes</strong>
    </div>
    <?php elseif ($order['status']==='delivered'): ?>
    <div style="text-align:center;margin-top:10px;font-size:14px;color:var(--green);font-weight:600">
      ✅ Delivered on <?= $order['delivered_at'] ? date('d M Y', strtotime($order['delivered_at'])) : 'time' ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="grid-2" style="gap:18px;align-items:start">
  <div>
    <!-- Order Items -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-shopping-bag-line"></i>Items Ordered</div></div>
      <div class="card-body" style="padding:0">
        <?php foreach ($order['items'] as $item): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
          <div style="width:56px;height:56px;border-radius:10px;overflow:hidden;background:var(--bg-input);flex-shrink:0">
            <?php if ($item['image']): ?><img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($item['image']) ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?><div style="display:grid;place-items:center;height:100%;font-size:24px">🍽️</div><?php endif; ?>
          </div>
          <div style="flex:1">
            <div class="fw-bold"><?= sanitize($item['name']) ?></div>
            <div class="text-sm text-muted"><?= formatPrice($item['price']) ?> × <?= $item['quantity'] ?></div>
          </div>
          <div class="fw-bold text-amber"><?= formatPrice($item['subtotal']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="padding:16px 20px;background:var(--bg-input)">
          <div class="summary-row" style="margin-bottom:6px"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
          <div class="summary-row" style="margin-bottom:6px"><span>Delivery</span><span><?= $order['delivery_fee']>0?formatPrice($order['delivery_fee']):'<span class="text-success">FREE</span>' ?></span></div>
          <?php if ($order['discount']>0): ?><div class="summary-row" style="color:var(--green);margin-bottom:6px"><span>Discount</span><span>-<?= formatPrice($order['discount']) ?></span></div><?php endif; ?>
          <div class="summary-row total" style="margin-top:8px"><span>Total Paid</span><span><?= formatPrice($order['total']) ?></span></div>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Order Info -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-information-line"></i>Order Info</div></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:14px">
          <div style="display:flex;justify-content:space-between"><span class="text-muted">Order Number</span><span class="fw-bold text-amber"><?= sanitize($order['order_number']) ?></span></div>
          <div style="display:flex;justify-content:space-between"><span class="text-muted">Placed On</span><span><?= formatDate($order['created_at']) ?></span></div>
          <div style="display:flex;justify-content:space-between"><span class="text-muted">Status</span><span><?= statusBadge($order['status']) ?></span></div>
          <div style="display:flex;justify-content:space-between"><span class="text-muted">Payment</span><span><?= strtoupper($order['payment_method']) ?></span></div>
          <div style="display:flex;justify-content:space-between"><span class="text-muted">Payment Status</span><span><?= statusBadge($order['payment_status']) ?></span></div>
        </div>
      </div>
    </div>

    <!-- Delivery Address -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-map-pin-line"></i>Delivery Address</div></div>
      <div class="card-body">
        <p style="font-size:14px;line-height:1.6"><?= sanitize($order['delivery_address']) ?></p>
        <?php if ($order['notes']): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg-input);border-radius:var(--radius-sm)">
          <div class="text-sm text-muted">Instructions</div>
          <div style="font-size:13px"><?= sanitize($order['notes']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($order['status'] === 'delivered'): ?>
    <!-- Rate Order -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="ri-star-line"></i>Rate Your Order</div></div>
      <div class="card-body">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px">How was your experience?</div>
        <div style="display:flex;gap:6px;margin-bottom:14px" id="stars">
          <?php for ($i=1;$i<=5;$i++): ?>
          <button onclick="setRating(<?= $i ?>)" style="background:none;border:none;font-size:28px;cursor:pointer;filter:grayscale(1);transition:filter 0.2s" class="star-btn" data-r="<?= $i ?>">⭐</button>
          <?php endfor; ?>
        </div>
        <textarea class="form-control" id="reviewText" rows="2" placeholder="Write a review..."></textarea>
        <button class="btn btn-primary btn-sm" style="margin-top:10px" onclick="submitReview()"><i class="ri-send-plane-line"></i> Submit Review</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
let selectedRating = 0;
function setRating(r) {
  selectedRating = r;
  document.querySelectorAll('.star-btn').forEach((btn,i) => {
    btn.style.filter = i < r ? 'none' : 'grayscale(1)';
  });
}
function submitReview() {
  if (!selectedRating) { showToast('Please select a rating','warning'); return; }
  showToast('Thank you for your review! 🎉','success');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
