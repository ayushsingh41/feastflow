<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['_ff_csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pdo->prepare("INSERT INTO coupons (code,discount_type,discount,min_order,max_uses,expires_at) VALUES (?,?,?,?,?,?)")
            ->execute([strtoupper(trim($_POST['code'])), $_POST['discount_type'], $_POST['discount'], $_POST['min_order']??0, $_POST['max_uses']??100, $_POST['expires_at']??null]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Coupon created!'];
    } elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("SELECT status FROM coupons WHERE id=?"); $stmt->execute([$_POST['coupon_id']]);
        $cur  = $stmt->fetchColumn();
        $pdo->prepare("UPDATE coupons SET status=? WHERE id=?")->execute([$cur==='active'?'inactive':'active', $_POST['coupon_id']]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Coupon status updated.'];
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([$_POST['coupon_id']]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Coupon deleted.'];
    }
    header('Location: '.APP_URL.'/admin/coupons.php'); exit;
}

$coupons   = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$pageTitle = 'Coupons';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>Coupons</div>
    <h1 class="page-title">Manage <span>Coupons</span></h1>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')"><i class="ri-add-line"></i> Create Coupon</button>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-coupon-3-line"></i>All Coupons (<?= count($coupons) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Usage</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($coupons as $c): ?>
      <tr>
        <td>
          <div style="font-family:monospace;font-size:15px;font-weight:700;color:var(--amber);letter-spacing:0.05em"><?= sanitize($c['code']) ?></div>
          <div class="text-sm text-muted"><?= $c['discount_type'] ?></div>
        </td>
        <td class="fw-bold">
          <?= $c['discount_type']==='percent' ? $c['discount'].'%' : formatPrice($c['discount']) ?>
        </td>
        <td><?= formatPrice($c['min_order']) ?></td>
        <td>
          <div style="font-size:13px"><?= $c['used_count'] ?> / <?= $c['max_uses'] ?></div>
          <div style="height:4px;border-radius:2px;background:var(--bg-input);margin-top:4px;overflow:hidden">
            <div style="height:100%;width:<?= min(100, ($c['used_count']/$c['max_uses'])*100) ?>%;background:var(--amber);border-radius:2px"></div>
          </div>
        </td>
        <td><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : '∞ No expiry' ?></td>
        <td><?= statusBadge($c['status']) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <form method="POST" style="display:inline">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm" title="Toggle status">
                <i class="ri-<?= $c['status']==='active'?'pause':'play' ?>-circle-line"></i>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete coupon <?= sanitize($c['code']) ?>?')">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-backdrop" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Create Coupon</div>
      <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')"><i class="ri-close-line"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Coupon Code <span class="req">*</span></label>
          <input type="text" name="code" class="form-control" placeholder="e.g. SUMMER25" oninput="this.value=this.value.toUpperCase()" required>
          <div class="form-hint">Letters & numbers only, no spaces</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Discount Type</label>
            <select name="discount_type" class="form-control">
              <option value="percent">Percentage (%)</option>
              <option value="fixed">Fixed Amount (₹)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Discount Value <span class="req">*</span></label>
            <input type="number" name="discount" class="form-control" placeholder="e.g. 20" step="0.01" min="0" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Min Order Amount (₹)</label>
            <input type="number" name="min_order" class="form-control" value="0" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Max Uses</label>
            <input type="number" name="max_uses" class="form-control" value="100" min="1">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expires_at" class="form-control" min="<?= date('Y-m-d') ?>">
          <div class="form-hint">Leave empty for no expiry</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Create Coupon</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
