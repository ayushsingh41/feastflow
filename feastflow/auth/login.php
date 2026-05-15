<?php
require_once __DIR__ . '/../includes/auth.php';
requireGuest();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_ff_csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $result = loginUser(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            $redirect = ($result['role'] === 'admin') ? '/admin/dashboard.php' : '/customer/dashboard.php';
            header('Location: ' . APP_URL . $redirect);
            exit;
        }
        $error = $result['message'];
    }
}
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In | FeastFlow</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<div class="auth-page">
  <!-- Visual Side -->
  <div class="auth-visual">
    <div style="position:relative;z-index:1;text-align:center">
      <div style="font-size:72px;margin-bottom:20px">🍽️</div>
      <div class="auth-visual-title">Delicious Food<br><span>Delivered Fast</span></div>
      <div class="auth-visual-sub">Order your favorite meals from the<br>comfort of your home</div>
      <div class="auth-visual-features">
        <div class="auth-feature"><i class="ri-timer-flash-line"></i> Lightning fast delivery in 30 mins</div>
        <div class="auth-feature"><i class="ri-shield-check-line"></i> 100% safe & hygienic preparation</div>
        <div class="auth-feature"><i class="ri-star-line"></i> 4.8★ rated by 10,000+ customers</div>
        <div class="auth-feature"><i class="ri-coupon-3-line"></i> Exclusive discounts & offers</div>
      </div>
    </div>
  </div>

  <!-- Form Side -->
  <div class="auth-form-side">
    <div class="auth-box">
      <div class="auth-logo">
        <div class="logo-icon"><i class="ri-restaurant-fill"></i></div>
        Feast<span>Flow</span>
      </div>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-subtitle">Sign in to your account to continue</p>

      <?php if ($error): ?>
      <div class="alert alert-error"><i class="ri-error-warning-line"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Email Address <span class="req">*</span></label>
          <div class="input-group">
            <i class="input-icon ri-mail-line"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between">
            Password <span class="req">*</span>
            <a href="<?= APP_URL ?>/auth/forgot-password.php" style="color:var(--amber);font-weight:500;font-size:12px">Forgot password?</a>
          </label>
          <div class="input-group" style="position:relative">
            <i class="input-icon ri-lock-line"></i>
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
            <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);font-size:16px"><i class="ri-eye-line" id="eyeIcon"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="ri-login-box-line"></i> Sign In
        </button>
      </form>

      <div class="auth-divider">or try demo accounts</div>

      <div class="grid-2" style="gap:10px;margin-bottom:16px">
        <button class="btn btn-outline" onclick="fillDemo('admin@feastflow.com','Admin@123')">
          <i class="ri-shield-user-line"></i> Admin Demo
        </button>
        <button class="btn btn-outline" onclick="fillDemo('rahul@example.com','Admin@123')">
          <i class="ri-user-line"></i> Customer Demo
        </button>
      </div>

      <p class="auth-footer-text">Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Create one free</a></p>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function fillDemo(email, pass) {
  document.querySelector('[name=email]').value = email;
  document.querySelector('[name=password]').value = pass;
}
function togglePwd() {
  const inp = document.getElementById('passwordInput');
  const ico = document.getElementById('eyeIcon');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'ri-eye-line' : 'ri-eye-off-line';
}
</script>
</body>
</html>
