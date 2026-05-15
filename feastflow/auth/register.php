<?php
require_once __DIR__ . '/../includes/auth.php';
requireGuest();

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_ff_csrf'] ?? '')) { $error = 'Invalid request.'; }
    elseif (strlen($_POST['password']) < 8) { $error = 'Password must be at least 8 characters.'; }
    elseif ($_POST['password'] !== $_POST['confirm_password']) { $error = 'Passwords do not match.'; }
    else {
        $result = registerUser([
            'name'  => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'phone' => trim($_POST['phone'] ?? ''),
        ]);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/auth/login.php?registered=1');
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | FeastFlow</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-visual">
    <div style="position:relative;z-index:1;text-align:center">
      <div style="font-size:72px;margin-bottom:20px">🎉</div>
      <div class="auth-visual-title">Join <span>FeastFlow</span><br>Today!</div>
      <div class="auth-visual-sub">Get exclusive deals, track orders<br>and enjoy seamless food ordering</div>
      <div class="auth-visual-features">
        <div class="auth-feature"><i class="ri-gift-line"></i> ₹50 off on your first order</div>
        <div class="auth-feature"><i class="ri-truck-line"></i> Free delivery on orders above ₹500</div>
        <div class="auth-feature"><i class="ri-notification-line"></i> Real-time order tracking</div>
        <div class="auth-feature"><i class="ri-history-line"></i> Easy reorder from history</div>
      </div>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <div class="auth-logo">
        <div class="logo-icon"><i class="ri-restaurant-fill"></i></div>
        Feast<span>Flow</span>
      </div>
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-subtitle">Start ordering delicious food today</p>

      <?php if ($error): ?>
      <div class="alert alert-error"><i class="ri-error-warning-line"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <?= csrfField() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="req">*</span></label>
            <div class="input-group"><i class="input-icon ri-user-line"></i>
              <input type="text" name="name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <div class="input-group"><i class="input-icon ri-phone-line"></i>
              <input type="tel" name="phone" class="form-control" placeholder="9876543210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address <span class="req">*</span></label>
          <div class="input-group"><i class="input-icon ri-mail-line"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password <span class="req">*</span></label>
            <div class="input-group"><i class="input-icon ri-lock-line"></i>
              <input type="password" name="password" class="form-control" placeholder="Min 8 characters" id="pwd" oninput="checkStrength(this.value)" required>
            </div>
            <div class="pwd-strength" id="pwdStrength" style="display:none;margin-top:6px">
              <div style="height:4px;border-radius:2px;background:var(--border);overflow:hidden">
                <div id="strengthBar" style="height:100%;width:0;transition:all 0.3s;border-radius:2px"></div>
              </div>
              <span id="strengthLabel" style="font-size:11px;color:var(--text-muted)"></span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password <span class="req">*</span></label>
            <div class="input-group"><i class="input-icon ri-lock-2-line"></i>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" id="terms" required>
            <label for="terms" style="font-size:13px;color:var(--text-muted)">I agree to the <a href="#" style="color:var(--amber)">Terms of Service</a> and <a href="#" style="color:var(--amber)">Privacy Policy</a></label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="ri-user-add-line"></i> Create Account
        </button>
      </form>
      <p class="auth-footer-text">Already have an account? <a href="<?= APP_URL ?>/auth/login.php">Sign in</a></p>
    </div>
  </div>
</div>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
function checkStrength(v) {
  const bar = document.getElementById('strengthBar');
  const lbl = document.getElementById('strengthLabel');
  document.getElementById('pwdStrength').style.display = v.length > 0 ? '' : 'none';
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const colors = ['#ef4444','#f59e0b','#3b82f6','#10b981'];
  const labels = ['Weak','Fair','Good','Strong'];
  bar.style.width = (score * 25) + '%';
  bar.style.background = colors[score - 1] || '#ef4444';
  lbl.textContent = labels[score - 1] || '';
}
</script>
</body>
</html>
