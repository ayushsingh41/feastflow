<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$filters = [
    'user_id' => $userId,
    'status'  => $_GET['status'] ?? '',
];

$orders     = getOrders($filters);
$page       = max(1,(int)($_GET['page']??1));
$total      = count($orders);
$pages      = max(1, ceil($total / ROWS_PER_PAGE));
$orders     = array_slice($orders, ($page-1)*ROWS_PER_PAGE, ROWS_PER_PAGE);
$statusList = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
$pageTitle  = 'My Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i>My Orders</div>
    <h1 class="page-title">My <span>Orders</span></h1>
    <p class="page-subtitle"><?= $total ?> order<?= $total!=1?'s':'' ?> placed</p>
  </div>
  <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-primary"><i class="ri-add-line"></i> New Order</a>
</div>

<!-- Status Filter -->
<div class="category-pills mb-16">
  <a href="?status=" class="cat-pill <?= empty($_GET['status'])?'active':'' ?>">All Orders</a>
  <?php foreach ($statusList as $s): ?>
  <a href="?status=<?= $s ?>" class="cat-pill <?= ($_GET['status']??'')===$s?'active':'' ?>"><?= ucwords(str_replace('_',' ',$s)) ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state">
  <i class="ri-file-list-3-line"></i>
  <h3><?= !empty($_GET['status']) ? ucwords(str_replace('_',' ',$_GET['status'])).' orders' : 'No orders yet' ?></h3>
  <p>You haven't placed any orders<?= !empty($_GET['status']) ? ' with this status' : '' ?> yet.</p>
  <a href="<?= APP_URL ?>/customer/menu.php" class="btn btn-primary"><i class="ri-restaurant-2-line"></i> Browse Menu</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
  <?php foreach ($orders as $o): ?>
  <div class="order-card">
    <div class="order-header">
      <div>
        <div class="order-number"><?= sanitize($o['order_number']) ?></div>
        <div class="order-date"><i class="ri-time-line"></i> <?= formatDate($o['created_at']) ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <?= statusBadge($o['status']) ?>
        <a href="<?= APP_URL ?>/customer/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm"><i class="ri-eye-line"></i> View Details</a>
      </div>
    </div>

    <!-- Progress Tracker -->
    <?php
    $steps = ['pending','confirmed','preparing','out_for_delivery','delivered'];
    $currentIdx = array_search($o['status'], $steps);
    $isCancelled = $o['status'] === 'cancelled';
    ?>
    <?php if (!$isCancelled): ?>
    <div style="margin:14px 0;overflow-x:auto">
      <div style="display:flex;align-items:center;min-width:500px">
        <?php foreach ($steps as $i => $step): ?>
        <?php $done = $currentIdx !== false && $i <= $currentIdx; ?>
        <div style="flex:1;text-align:center;position:relative">
          <?php if ($i < count($steps)-1): ?>
          <div style="position:absolute;top:14px;left:50%;right:-50%;height:2px;background:<?= ($currentIdx!==false&&$i<$currentIdx)?'var(--amber)':'var(--border)' ?>;z-index:0"></div>
          <?php endif; ?>
          <div style="width:28px;height:28px;border-radius:50%;background:<?= $done?'var(--amber)':'var(--bg-input)' ?>;border:2px solid <?= $done?'var(--amber)':'var(--border)' ?>;display:inline-grid;place-items:center;position:relative;z-index:1;font-size:13px;color:<?= $done?'#000':'var(--text-muted)' ?>">
            <?= $done?'✓':($i+1) ?>
          </div>
          <div style="font-size:10px;margin-top:4px;color:<?= $done?'var(--amber)':'var(--text-muted)' ?>;font-weight:<?= $done?'600':'400' ?>"><?= ucwords(str_replace('_',' ',$step)) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-error" style="margin:10px 0;padding:8px 12px"><i class="ri-close-circle-line"></i>Order was cancelled</div>
    <?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div style="font-size:13px;color:var(--text-muted)"><?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?> · <?= strtoupper($o['payment_method']) ?></div>
      <div style="font-size:18px;font-weight:700;color:var(--amber)"><?= formatPrice($o['total']) ?></div>
    </div>

    <?php if (in_array($o['status'], ['pending','confirmed'])): ?>
    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border)">
      <a href="?cancel=<?= $o['id'] ?>" class="btn btn-outline btn-sm text-danger" data-confirm="Cancel this order?"><i class="ri-close-circle-line"></i> Cancel Order</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination" style="margin-top:20px">
  <?php if ($page > 1): ?><a href="?status=<?= urlencode($_GET['status']??'') ?>&page=<?= $page-1 ?>" class="page-btn"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
  <?php for ($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
  <a href="?status=<?= urlencode($_GET['status']??'') ?>&page=<?= $i ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($page < $pages): ?><a href="?status=<?= urlencode($_GET['status']??'') ?>&page=<?= $page+1 ?>" class="page-btn"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
