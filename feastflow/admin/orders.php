<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

// AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    if (!verifyCsrf($_POST['_ff_csrf'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }
    $ok = updateOrderStatus((int)$_POST['order_id'], $_POST['status']);
    logActivity($_SESSION['user_id'], 'ORDER_STATUS', 'Order #'.$_POST['order_id'].' → '.$_POST['status']);
    echo json_encode(['success'=>$ok,'message'=> $ok ? 'Status updated!' : 'Update failed.']);
    exit;
}

$filters = [
    'status'    => $_GET['status']    ?? '',
    'search'    => $_GET['search']    ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to']   ?? '',
];
$orders = getOrders($filters);
$page   = max(1,(int)($_GET['page']??1));
$total  = count($orders);
$pages  = max(1, ceil($total / ROWS_PER_PAGE));
$orders = array_slice($orders, ($page-1)*ROWS_PER_PAGE, ROWS_PER_PAGE);

$statusList = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
$pageTitle  = 'Manage Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>Orders</div>
    <h1 class="page-title">Manage <span>Orders</span></h1>
    <p class="page-subtitle">Track and manage all customer orders</p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="?<?= http_build_query(array_merge($filters,['export'=>'csv'])) ?>" class="btn btn-outline"><i class="ri-download-2-line"></i> Export</a>
  </div>
</div>

<!-- Status Filter Pills -->
<div class="category-pills mb-16">
  <a href="?status=&search=<?= urlencode($filters['search']) ?>" class="cat-pill <?= empty($filters['status'])?'active':'' ?>">All Orders</a>
  <?php foreach ($statusList as $s): ?>
  <a href="?status=<?= $s ?>&search=<?= urlencode($filters['search']) ?>" class="cat-pill <?= $filters['status']===$s?'active':'' ?>"><?= ucwords(str_replace('_',' ',$s)) ?></a>
  <?php endforeach; ?>
</div>

<!-- Search & Date Filters -->
<form method="GET" action="">
  <input type="hidden" name="status" value="<?= sanitize($filters['status']) ?>">
  <div class="filters-bar">
    <div class="search-filter">
      <i class="ri-search-line"></i>
      <input type="text" name="search" placeholder="Search orders, customers..." value="<?= sanitize($filters['search']) ?>">
    </div>
    <input type="date" name="date_from" class="filter-select" value="<?= $filters['date_from'] ?>" placeholder="From date">
    <input type="date" name="date_to"   class="filter-select" value="<?= $filters['date_to']   ?>" placeholder="To date">
    <button type="submit" class="btn btn-outline btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
    <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-outline btn-sm"><i class="ri-refresh-line"></i> Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-file-list-3-line"></i>Orders (<?= $total ?>)</div>
  </div>
  <?php if (empty($orders)): ?>
  <div class="empty-state"><i class="ri-file-list-3-line"></i><h3>No orders found</h3><p>Try adjusting your filters.</p></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
      <tr data-searchable="<?= sanitize($o['order_number'].' '.$o['customer_name'].' '.$o['customer_email']) ?>">
        <td><a href="<?= APP_URL ?>/admin/view-order.php?id=<?= $o['id'] ?>" class="text-amber fw-bold"><?= sanitize($o['order_number']) ?></a></td>
        <td>
          <div class="fw-bold"><?= sanitize($o['customer_name']) ?></div>
          <div class="text-sm text-muted"><?= sanitize($o['customer_email']) ?></div>
        </td>
        <td><?= $o['item_count'] ?> item(s)</td>
        <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
        <td>
          <div><?= strtoupper($o['payment_method']) ?></div>
          <?= statusBadge($o['payment_status']) ?>
        </td>
        <td>
          <select class="filter-select status-select" data-order-id="<?= $o['id'] ?>" style="padding:4px 8px;font-size:12px">
            <?php foreach ($statusList as $s): ?>
            <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <div><?= date('d M Y', strtotime($o['created_at'])) ?></div>
          <div class="text-sm text-muted"><?= timeAgo($o['created_at']) ?></div>
        </td>
        <td>
          <a href="<?= APP_URL ?>/admin/view-order.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm"><i class="ri-eye-line"></i> View</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div style="padding:14px 20px;border-top:1px solid var(--border)">
    <div class="pagination">
      <?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($filters,['page'=>$page-1])) ?>" class="page-btn"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
      <a href="?<?= http_build_query(array_merge($filters,['page'=>$i])) ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="?<?= http_build_query(array_merge($filters,['page'=>$page+1])) ?>" class="page-btn"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
window.CSRF_TOKEN = '<?= generateCsrf() ?>';
window.APP_URL    = '<?= APP_URL ?>';
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
