<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

// Toggle user status
if ($_GET['action'] ?? '' === 'toggle' && isset($_GET['id'])) {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $u = $stmt->fetch();
    if ($u) {
        $new = $u['status'] === 'active' ? 'inactive' : 'active';
        $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$new, $_GET['id']]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'User status updated.'];
    }
    header('Location: '.APP_URL.'/admin/users.php'); exit;
}

$search = $_GET['search'] ?? '';
$role   = $_GET['role']   ?? '';
$status = $_GET['status'] ?? '';

$pdo = getPDO();
$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id=u.id AND o.status='delivered') AS total_spent FROM users u WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)"; $s='%'.$search.'%'; $params[]=$s;$params[]=$s;$params[]=$s; }
if ($role)   { $sql .= " AND u.role=?";   $params[] = $role; }
if ($status) { $sql .= " AND u.status=?"; $params[] = $status; }
$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();

$page   = max(1,(int)($_GET['page']??1));
$total  = count($users);
$pages  = max(1, ceil($total / ROWS_PER_PAGE));
$users  = array_slice($users, ($page-1)*ROWS_PER_PAGE, ROWS_PER_PAGE);

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>Users</div>
    <h1 class="page-title">Manage <span>Users</span></h1>
    <p class="page-subtitle">View and manage all registered users</p>
  </div>
</div>

<form method="GET">
  <div class="filters-bar">
    <div class="search-filter"><i class="ri-search-line"></i>
      <input type="text" name="search" placeholder="Search users..." value="<?= sanitize($search) ?>">
    </div>
    <select name="role" class="filter-select">
      <option value="">All Roles</option>
      <option value="admin"    <?= $role==='admin'   ?'selected':'' ?>>Admin</option>
      <option value="customer" <?= $role==='customer'?'selected':'' ?>>Customer</option>
    </select>
    <select name="status" class="filter-select">
      <option value="">All Status</option>
      <option value="active"   <?= $status==='active'  ?'selected':'' ?>>Active</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      <option value="banned"   <?= $status==='banned'  ?'selected':'' ?>>Banned</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
    <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline btn-sm"><i class="ri-refresh-line"></i> Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-group-line"></i>Users (<?= $total ?>)</div></div>
  <?php if (empty($users)): ?>
  <div class="empty-state"><i class="ri-group-line"></i><h3>No users found</h3><p>Try adjusting your filters.</p></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>User</th><th>Role</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr data-searchable="<?= sanitize($u['name'].' '.$u['email']) ?>">
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:38px;height:38px;border-radius:50%;background:var(--amber-glow);border:1px solid var(--amber);display:grid;place-items:center;font-weight:700;color:var(--amber);flex-shrink:0">
              <?= strtoupper(substr($u['name'],0,1)) ?>
            </div>
            <div>
              <div class="fw-bold"><?= sanitize($u['name']) ?></div>
              <div class="text-sm text-muted"><?= sanitize($u['email']) ?></div>
            </div>
          </div>
        </td>
        <td><?= $u['role'] === 'admin' ? '<span class="badge badge-warning">Admin</span>' : '<span class="badge badge-info">Customer</span>' ?></td>
        <td><?= $u['order_count'] ?></td>
        <td><?= formatPrice($u['total_spent']) ?></td>
        <td><?= statusBadge($u['status']) ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
          <a href="?action=toggle&id=<?= $u['id'] ?>" class="btn <?= $u['status']==='active'?'btn-outline':'btn-success' ?> btn-sm">
            <i class="ri-<?= $u['status']==='active'?'pause':'play' ?>-circle-line"></i>
            <?= $u['status']==='active'?'Deactivate':'Activate' ?>
          </a>
          <?php else: ?><span class="text-sm text-muted">Current user</span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div style="padding:14px 20px;border-top:1px solid var(--border)">
    <div class="pagination">
      <?php if ($page > 1): ?><a href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="page-btn"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
      <?php for ($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
      <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="page-btn"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
