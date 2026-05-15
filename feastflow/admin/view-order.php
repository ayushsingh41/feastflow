<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

$order = getOrderById((int)($_GET['id'] ?? 0));
if (!$order) { header('Location: '.APP_URL.'/admin/orders.php'); exit; }

// Update status form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['_ff_csrf'] ?? '')) {
    updateOrderStatus($order['id'], $_POST['status']);
    logActivity($_SESSION['user_id'], 'ORDER_STATUS_UPDATE', 'Order '.$order['order_number'].' → '.$_POST['status']);
    $_SESSION['flash'][] = ['type'=>'success','message'=>'Order status updated!'];
    header('Location: ?id='.$order['id']); exit;
}

$statusList = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
$pageTitle  = 'Order ' . $order['order_number'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i><a href="<?= APP_URL ?>/admin/orders.php">Orders</a><i class="ri-arrow-right-s-line"></i><?= sanitize($order['order_number']) ?></div>
    <h1 class="page-title">Order <span>#<?= sanitize($order['order_number']) ?></span></h1>
  </div>
  <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div class="grid-2" style="gap:18px;align-items:start">
  <div>
    <!-- Order Items -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-shopping-bag-line"></i>Order Items</div></div>
      <div class="card-body" style="padding:0">
        <?php foreach ($order['items'] as $item): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
          <div style="width:52px;height:52px;border-radius:10px;overflow:hidden;background:var(--bg-input);flex-shrink:0">
            <?php if ($item['image']): ?><img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($item['image']) ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?><div style="display:grid;place-items:center;height:100%;font-size:22px">🍽️</div><?php endif; ?>
          </div>
          <div style="flex:1">
            <div class="fw-bold"><?= sanitize($item['name']) ?></div>
            <div class="text-sm text-muted"><?= formatPrice($item['price']) ?> × <?= $item['quantity'] ?></div>
          </div>
          <div class="fw-bold text-amber"><?= formatPrice($item['subtotal']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="padding:16px 20px">
          <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
          <div class="summary-row"><span>Delivery Fee</span><span><?= $order['delivery_fee'] > 0 ? formatPrice($order['delivery_fee']) : '<span class="text-success">FREE</span>' ?></span></div>
          <?php if ($order['discount'] > 0): ?><div class="summary-row text-success"><span>Discount</span><span>-<?= formatPrice($order['discount']) ?></span></div><?php endif; ?>
          <div class="summary-row total"><span>Total</span><span><?= formatPrice($order['total']) ?></span></div>
        </div>
      </div>
    </div>

    <!-- Update Status -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="ri-refresh-line"></i>Update Status</div></div>
      <div class="card-body">
        <form method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">Order Status</label>
            <select name="status" class="form-control">
              <?php foreach ($statusList as $s): ?>
              <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Update Status</button>
        </form>
      </div>
    </div>
  </div>

  <div>
    <!-- Customer Info -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-user-line"></i>Customer Details</div></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:12px">
          <div><div class="text-sm text-muted">Name</div><div class="fw-bold"><?= sanitize($order['customer_name']) ?></div></div>
          <div><div class="text-sm text-muted">Email</div><div><?= sanitize($order['customer_email']) ?></div></div>
          <div><div class="text-sm text-muted">Phone</div><div><?= sanitize($order['customer_phone'] ?? 'N/A') ?></div></div>
          <div><div class="text-sm text-muted">Delivery Address</div><div><?= sanitize($order['delivery_address']) ?></div></div>
          <?php if ($order['notes']): ?><div><div class="text-sm text-muted">Notes</div><div><?= sanitize($order['notes']) ?></div></div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Order Info -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="ri-information-line"></i>Order Info</div></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:12px">
          <div><div class="text-sm text-muted">Order Number</div><div class="fw-bold text-amber"><?= sanitize($order['order_number']) ?></div></div>
          <div><div class="text-sm text-muted">Placed On</div><div><?= formatDate($order['created_at']) ?></div></div>
          <div><div class="text-sm text-muted">Status</div><div><?= statusBadge($order['status']) ?></div></div>
          <div><div class="text-sm text-muted">Payment</div><div><?= strtoupper($order['payment_method']) ?> · <?= statusBadge($order['payment_status']) ?></div></div>
          <?php if ($order['delivered_at']): ?><div><div class="text-sm text-muted">Delivered At</div><div><?= formatDate($order['delivered_at']) ?></div></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
