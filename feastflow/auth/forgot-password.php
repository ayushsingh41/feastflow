<?php
require_once __DIR__ . '/../includes/auth.php';
requireGuest();
$message = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $stmt  = getPDO()->prepare("SELECT id FROM users WHERE email=?");
    $stmt->execute([$email]);
    // We always show success to avoid email enumeration
    $message = "If an account exists with that email, you'll receive a reset link shortly. For this demo, use: Admin@123";
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | FeastFlow</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-visual">
    <div style="position:relative;z-index:1;text-align:center">
      <div style="font-size:72px;margin-bottom:20px">🔐</div>
      <div class="auth-visual-title">Reset Your<br><span>Password</span></div>
      <div class="auth-visual-sub">We'll help you get back into your account safely.</div>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <div class="auth-logo"><div class="logo-icon"><i class="ri-restaurant-fill"></i></div>Feast<span>Flow</span></div>
      <h1 class="auth-title">Forgot password?</h1>
      <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>

      <?php if ($message): ?><div class="alert alert-success"><i class="ri-check-circle-line"></i><?= htmlspecialchars($message) ?></div><?php endif; ?>

      <form method="POST">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-group"><i class="input-icon ri-mail-line"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="ri-send-plane-line"></i> Send Reset Link</button>
      </form>
      <p class="auth-footer-text"><a href="<?= APP_URL ?>/auth/login.php"><i class="ri-arrow-left-line"></i> Back to Sign In</a></p>
    </div>
  </div>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
