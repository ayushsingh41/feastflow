<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$user   = currentUser();
$error  = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['_ff_csrf'] ?? '')) {
    $action = $_POST['form_action'] ?? '';
    $pdo    = getPDO();

    if ($action === 'profile') {
        // Avatar upload
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $newAvatar = uploadImage($_FILES['avatar'], UPLOAD_DIR);
            if ($newAvatar) $avatar = $newAvatar;
        }
        $pdo->prepare("UPDATE users SET name=?,phone=?,address=?,avatar=? WHERE id=?")
            ->execute([trim($_POST['name']), trim($_POST['phone']??''), trim($_POST['address']??''), $avatar, $userId]);
        $_SESSION['user_name']   = trim($_POST['name']);
        $_SESSION['user_avatar'] = $avatar;
        $success = 'Profile updated successfully!';
        $user    = currentUser();
    }

    if ($action === 'password') {
        if (!password_verify($_POST['current_password'], $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'New passwords do not match.';
        } elseif (strlen($_POST['new_password']) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost'=>BCRYPT_COST]);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $userId]);
            $success = 'Password changed successfully!';
            logActivity($userId, 'PASSWORD_CHANGE', 'User changed password');
        }
    }
}

// Stats
$pdo = getPDO();
$stats['orders']    = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $stats['orders']->execute([$userId]); $stats['orders'] = $stats['orders']->fetchColumn();
$stats['spent']     = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND status='delivered'"); $stats['spent']->execute([$userId]); $stats['spent'] = $stats['spent']->fetchColumn();
$stats['cancelled'] = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND status='cancelled'"); $stats['cancelled']->execute([$userId]); $stats['cancelled'] = $stats['cancelled']->fetchColumn();

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/customer/dashboard.php">Home</a><i class="ri-arrow-right-s-line"></i>Profile</div>
    <h1 class="page-title">My <span>Profile</span></h1>
  </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><i class="ri-error-warning-line"></i><?= sanitize($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="ri-check-circle-line"></i><?= sanitize($success) ?></div><?php endif; ?>

<div class="grid-2" style="gap:18px;align-items:start">
  <div>
    <!-- Profile Card -->
    <div class="card mb-16">
      <div class="card-body" style="text-align:center;padding:32px">
        <div style="position:relative;display:inline-block;margin-bottom:16px">
          <div style="width:90px;height:90px;border-radius:50%;background:var(--amber);display:grid;place-items:center;font-family:var(--font-display);font-size:36px;font-weight:700;color:#000;overflow:hidden;border:3px solid var(--amber);margin:0 auto">
            <?php if ($user['avatar']): ?>
            <img src="<?= APP_URL ?>/assets/uploads/products/<?= sanitize($user['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" id="avatarPreview">
            <?php else: ?>
            <span id="avatarInitial"><?= strtoupper(substr($user['name'],0,1)) ?></span>
            <img id="avatarPreview" style="display:none;width:100%;height:100%;object-fit:cover">
            <?php endif; ?>
          </div>
          <label style="position:absolute;bottom:0;right:0;width:28px;height:28px;background:var(--amber);border-radius:50%;display:grid;place-items:center;cursor:pointer;border:2px solid var(--bg)">
            <i class="ri-camera-line" style="font-size:14px;color:#000"></i>
            <input type="file" accept="image/*" style="display:none" id="avatarInput" onchange="previewAvatar(this)">
          </label>
        </div>
        <div style="font-family:var(--font-display);font-size:22px;font-weight:700"><?= sanitize($user['name']) ?></div>
        <div style="color:var(--text-muted);font-size:13px"><?= sanitize($user['email']) ?></div>
        <div style="margin-top:4px"><span class="badge badge-info">Customer</span></div>
        <div style="display:flex;justify-content:center;gap:24px;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
          <div style="text-align:center"><div style="font-size:22px;font-weight:700;color:var(--amber)"><?= $stats['orders'] ?></div><div style="font-size:11px;color:var(--text-muted)">Orders</div></div>
          <div style="text-align:center"><div style="font-size:22px;font-weight:700;color:var(--green)"><?= formatPrice((float)$stats['spent']) ?></div><div style="font-size:11px;color:var(--text-muted)">Total Spent</div></div>
          <div style="text-align:center"><div style="font-size:22px;font-weight:700;color:var(--red)"><?= $stats['cancelled'] ?></div><div style="font-size:11px;color:var(--text-muted)">Cancelled</div></div>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="ri-lock-line"></i>Change Password</div></div>
      <div class="card-body">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="password">
          <div class="form-group">
            <label class="form-label">Current Password <span class="req">*</span></label>
            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password <span class="req">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password <span class="req">*</span></label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
          </div>
          <button type="submit" class="btn btn-primary"><i class="ri-lock-password-line"></i> Update Password</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Profile -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-user-settings-line"></i>Edit Profile</div></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="profile">
        <input type="hidden" name="avatar_file" id="avatarFileHidden">
        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <div class="input-group"><i class="input-icon ri-user-line"></i>
            <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-group"><i class="input-icon ri-mail-line"></i>
            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled style="opacity:0.6">
          </div>
          <div class="form-hint">Email cannot be changed.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="input-group"><i class="input-icon ri-phone-line"></i>
            <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone']??'') ?>" placeholder="10-digit phone number">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Default Delivery Address</label>
          <textarea name="address" class="form-control" rows="3" placeholder="Your home/work address for quick checkout"><?= sanitize($user['address']??'') ?></textarea>
          <div class="form-hint">This will be auto-filled at checkout.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Profile Photo</label>
          <input type="file" name="avatar" class="form-control" accept="image/*" id="avatarFormInput">
        </div>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Save Changes</button>
      </form>
    </div>
  </div>
</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('avatarPreview');
      const initial = document.getElementById('avatarInitial');
      preview.src = e.target.result;
      preview.style.display = 'block';
      if (initial) initial.style.display = 'none';
      // Also set on the form input
      document.getElementById('avatarFormInput').files = input.files;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
