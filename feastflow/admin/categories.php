<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['_ff_csrf'] ?? '')) {
    $pdo = getPDO();
    if ($_POST['action'] === 'add') {
        $pdo->prepare("INSERT INTO categories (name,description,icon,color) VALUES (?,?,?,?)")
            ->execute([trim($_POST['name']), trim($_POST['description']??''), $_POST['icon']??'ri-restaurant-line', $_POST['color']??'#f59e0b']);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Category added!'];
    } elseif ($_POST['action'] === 'edit') {
        $pdo->prepare("UPDATE categories SET name=?,description=?,icon=?,color=?,status=? WHERE id=?")
            ->execute([trim($_POST['name']), trim($_POST['description']??''), $_POST['icon']??'ri-restaurant-line', $_POST['color']??'#f59e0b', $_POST['status'], $_POST['cat_id']]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Category updated!'];
    } elseif ($_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$_POST['cat_id']]);
        $_SESSION['flash'][] = ['type'=>'success','message'=>'Category deleted.'];
    }
    header('Location: '.APP_URL.'/admin/categories.php'); exit;
}

$categories = getCategories(false);
$pageTitle  = 'Categories';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>Categories</div>
    <h1 class="page-title">Manage <span>Categories</span></h1>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')"><i class="ri-add-line"></i> Add Category</button>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-apps-2-line"></i>Categories (<?= count($categories) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Category</th><th>Icon</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:10px;background:<?= htmlspecialchars($c['color']) ?>22;display:grid;place-items:center;color:<?= htmlspecialchars($c['color']) ?>;font-size:18px">
              <i class="<?= htmlspecialchars($c['icon']) ?>"></i>
            </div>
            <div>
              <div class="fw-bold"><?= sanitize($c['name']) ?></div>
              <div class="text-sm text-muted"><?= sanitize($c['description'] ?? '') ?></div>
            </div>
          </div>
        </td>
        <td class="text-sm text-muted"><?= sanitize($c['icon']) ?></td>
        <td><?= $c['product_count'] ?></td>
        <td><?= statusBadge($c['status']) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <button class="btn btn-outline btn-sm" onclick="editCat(<?= htmlspecialchars(json_encode($c)) ?>)"><i class="ri-edit-line"></i></button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
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
      <div class="modal-title">Add Category</div>
      <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')"><i class="ri-close-line"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Name <span class="req">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Icon Class</label><input type="text" name="icon" class="form-control" value="ri-restaurant-line" placeholder="e.g. ri-restaurant-line"></div>
          <div class="form-group"><label class="form-label">Color</label><input type="color" name="color" class="form-control" value="#f59e0b" style="height:42px;padding:4px"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Add Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Category</div>
      <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')"><i class="ri-close-line"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="cat_id" id="editCatId">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Name</label><input type="text" name="name" id="editCatName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Icon</label><input type="text" name="icon" id="editCatIcon" class="form-control"></div>
          <div class="form-group"><label class="form-label">Color</label><input type="color" name="color" id="editCatColor" class="form-control" style="height:42px;padding:4px"></div>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" id="editCatStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<script>
function editCat(c) {
  document.getElementById('editCatId').value    = c.id;
  document.getElementById('editCatName').value  = c.name;
  document.getElementById('editCatDesc').value  = c.description || '';
  document.getElementById('editCatIcon').value  = c.icon || '';
  document.getElementById('editCatColor').value = c.color || '#f59e0b';
  document.getElementById('editCatStatus').value= c.status;
  document.getElementById('editModal').classList.add('open');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
