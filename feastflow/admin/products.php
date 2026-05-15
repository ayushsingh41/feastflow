<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

$action  = $_GET['action'] ?? 'list';
$message = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_ff_csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $data = [
            'category_id' => (int)$_POST['category_id'],
            'name'        => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => (float)$_POST['price'],
            'stock'       => (int)($_POST['stock'] ?? 100),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_veg'      => isset($_POST['is_veg']) ? 1 : 0,
            'status'      => $_POST['status'] ?? 'active',
        ];

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $img = uploadImage($_FILES['image'], UPLOAD_DIR);
            if ($img) $data['image'] = $img;
        }

        if ($action === 'add') {
            $id = createProduct($data);
            if ($id) { $_SESSION['flash'][] = ['type'=>'success','message'=>'Product added successfully!']; header('Location: '.APP_URL.'/admin/products.php'); exit; }
            else $error = 'Failed to add product.';
        } elseif ($action === 'edit') {
            $id = (int)$_POST['product_id'];
            if (updateProduct($id, $data)) { $_SESSION['flash'][] = ['type'=>'success','message'=>'Product updated!']; header('Location: '.APP_URL.'/admin/products.php'); exit; }
            else $error = 'Failed to update product.';
        }
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    if (deleteProduct((int)$_GET['id'])) { $_SESSION['flash'][] = ['type'=>'success','message'=>'Product deleted.']; }
    header('Location: '.APP_URL.'/admin/products.php'); exit;
}

$categories = getCategories(false);

// Fetch product for editing
$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editProduct = getProductById((int)$_GET['id']);
    if (!$editProduct) { header('Location: '.APP_URL.'/admin/products.php'); exit; }
}

// Filters
$filters = ['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? '', 'category' => $_GET['category'] ?? ''];
$products = ($action === 'list') ? getProducts(array_merge($filters, ['status' => $filters['status'] ?: null])) : [];

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ROWS_PER_PAGE;
$total   = count($products);
$pages   = max(1, ceil($total / $perPage));
$products = array_slice($products, ($page-1)*$perPage, $perPage);

$pageTitle = ($action === 'add' ? 'Add Product' : ($action === 'edit' ? 'Edit Product' : 'Products'));
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>
    <?php if ($action !== 'list'): ?><a href="<?= APP_URL ?>/admin/products.php">Products</a><i class="ri-arrow-right-s-line"></i><?php endif; ?>
    <?= $pageTitle ?>
    </div>
    <h1 class="page-title"><?= $action === 'add' ? 'Add New <span>Product</span>' : ($action === 'edit' ? 'Edit <span>Product</span>' : 'Manage <span>Products</span>') ?></h1>
  </div>
  <?php if ($action === 'list'): ?>
  <a href="?action=add" class="btn btn-primary"><i class="ri-add-line"></i> Add Product</a>
  <?php else: ?>
  <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline"><i class="ri-arrow-left-line"></i> Back to List</a>
  <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-error"><i class="ri-error-warning-line"></i><?= sanitize($error) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Filters -->
<form method="GET" action="">
  <input type="hidden" name="action" value="list">
  <div class="filters-bar">
    <div class="search-filter">
      <i class="ri-search-line"></i>
      <input type="text" name="search" placeholder="Search products..." value="<?= sanitize($filters['search']) ?>">
    </div>
    <select name="category" class="filter-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $filters['category'] == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="filter-select">
      <option value="">All Status</option>
      <option value="active"   <?= $filters['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="ri-filter-3-line"></i> Filter</button>
    <a href="?action=list" class="btn btn-outline btn-sm"><i class="ri-refresh-line"></i> Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-restaurant-2-line"></i>Products (<?= $total ?>)</div>
  </div>
  <?php if (empty($products)): ?>
  <div class="empty-state"><i class="ri-restaurant-2-line"></i><h3>No products found</h3><p>Try adjusting your filters or add a new product.</p><a href="?action=add" class="btn btn-primary"><i class="ri-add-line"></i> Add Product</a></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Orders</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($products as $p): ?>
      <tr data-searchable="<?= sanitize($p['name']) . ' ' . sanitize($p['category_name']) ?>">
        <td>
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;border-radius:10px;overflow:hidden;background:var(--bg-input);flex-shrink:0">
              <?php if ($p['image']): ?>
              <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($p['image']) ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?><div style="display:grid;place-items:center;height:100%;color:var(--text-light);font-size:20px"><i class="ri-restaurant-2-line"></i></div>
              <?php endif; ?>
            </div>
            <div>
              <div class="fw-bold"><?= sanitize($p['name']) ?></div>
              <div style="display:flex;gap:4px;margin-top:3px">
                <?php if ($p['is_featured']): ?><span class="badge badge-warning" style="font-size:10px">★ Featured</span><?php endif; ?>
                <?php if ($p['is_veg']): ?><span class="badge badge-success" style="font-size:10px">Veg</span><?php else: ?><span class="badge badge-danger" style="font-size:10px">Non-veg</span><?php endif; ?>
              </div>
            </div>
          </div>
        </td>
        <td><span class="badge badge-info"><?= sanitize($p['category_name']) ?></span></td>
        <td class="fw-bold text-amber"><?= formatPrice($p['price']) ?></td>
        <td><?= $p['stock'] ?></td>
        <td><?= $p['total_orders'] ?></td>
        <td><?= statusBadge($p['status']) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm"><i class="ri-edit-line"></i></a>
            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Delete '<?= sanitize($p['name']) ?>'? This cannot be undone."><i class="ri-delete-bin-line"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="padding:14px 20px;border-top:1px solid var(--border)">
    <div class="pagination">
      <?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($filters,['action'=>'list','page'=>$page-1])) ?>" class="page-btn"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
      <a href="?<?= http_build_query(array_merge($filters,['action'=>'list','page'=>$i])) ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="?<?= http_build_query(array_merge($filters,['action'=>'list','page'=>$page+1])) ?>" class="page-btn"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php else: // Add / Edit form ?>
<div style="max-width:800px">
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <?php if ($editProduct): ?><input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>"><?php endif; ?>

    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-information-line"></i>Basic Information</div></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Product Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Classic Smash Burger" value="<?= sanitize($editProduct['name'] ?? '') ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category <span class="req">*</span></label>
            <select name="category_id" class="form-control" required>
              <option value="">Select category...</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Price (₹) <span class="req">*</span></label>
            <div class="input-group"><i class="input-icon ri-money-rupee-circle-line"></i>
              <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?= $editProduct['price'] ?? '' ?>" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Describe the product..."><?= sanitize($editProduct['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-image-line"></i>Product Image</div></div>
      <div class="card-body">
        <?php if (!empty($editProduct['image'])): ?>
        <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($editProduct['image']) ?>" id="imgPreview" style="width:120px;height:120px;object-fit:cover;border-radius:10px;margin-bottom:12px;border:1px solid var(--border)">
        <?php else: ?>
        <img id="imgPreview" style="display:none;width:120px;height:120px;object-fit:cover;border-radius:10px;margin-bottom:12px">
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/*" data-preview="imgPreview">
        <div class="form-hint">JPG, PNG, WEBP. Max 3MB.</div>
      </div>
    </div>

    <div class="card mb-16">
      <div class="card-header"><div class="card-title"><i class="ri-settings-3-line"></i>Settings</div></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <input type="number" name="stock" class="form-control" value="<?= $editProduct['stock'] ?? 100 ?>" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="active"   <?= ($editProduct['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= ($editProduct['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div style="display:flex;gap:20px">
          <div class="form-check">
            <input type="checkbox" name="is_featured" id="featured" <?= !empty($editProduct['is_featured']) ? 'checked' : '' ?>>
            <label for="featured">Mark as Featured</label>
          </div>
          <div class="form-check">
            <input type="checkbox" name="is_veg" id="veg" <?= !empty($editProduct['is_veg']) ? 'checked' : '' ?>>
            <label for="veg">Vegetarian</label>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> <?= $action === 'add' ? 'Add Product' : 'Update Product' ?></button>
      <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline"><i class="ri-close-line"></i> Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
