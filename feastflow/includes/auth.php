<?php
// ============================================================
// includes/auth.php — Authentication & Session Management
// ============================================================

require_once __DIR__ . '/../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ── CSRF ─────────────────────────────────────────────────────
function generateCsrf(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) &&
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="_ff_csrf" value="' . generateCsrf() . '">';
}

// ── Auth Checks ───────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/customer/dashboard.php');
        exit;
    }
}

function requireGuest(): void {
    if (isLoggedIn()) {
        $redirect = isAdmin() ? '/admin/dashboard.php' : '/customer/dashboard.php';
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

// ── Login ─────────────────────────────────────────────────────
function loginUser(string $email, string $password): array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) return ['success' => false, 'message' => 'Invalid email or password.'];
    if ($user['status'] === 'banned') return ['success' => false, 'message' => 'Your account has been suspended.'];

    // Lockout check
    if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS && $user['locked_until']) {
        if (strtotime($user['locked_until']) > time()) {
            $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => "Account locked. Try again in {$mins} minute(s)."];
        }
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['login_attempts'] + 1;
        $locked   = $attempts >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOCK_DURATION) : null;
        $pdo->prepare("UPDATE users SET login_attempts=?, locked_until=? WHERE id=?")
            ->execute([$attempts, $locked, $user['id']]);
        $left = MAX_LOGIN_ATTEMPTS - $attempts;
        $msg  = $left > 0 ? "Invalid password. {$left} attempt(s) remaining." : 'Account locked for 15 minutes.';
        return ['success' => false, 'message' => $msg];
    }

    // Success
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_avatar']= $user['avatar'];

    $pdo->prepare("UPDATE users SET login_attempts=0, locked_until=NULL, last_login=NOW() WHERE id=?")
        ->execute([$user['id']]);

    logActivity($user['id'], 'LOGIN', 'User logged in');

    return ['success' => true, 'role' => $user['role']];
}

// ── Register ──────────────────────────────────────────────────
function registerUser(array $data): array {
    $pdo = getPDO();

    // Check email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) return ['success' => false, 'message' => 'Email already registered.'];

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['name'], $data['email'], $hash, $data['phone'] ?? null]);
    $id = $pdo->lastInsertId();

    logActivity($id, 'REGISTER', 'New user registered');
    return ['success' => true];
}

// ── Logout ────────────────────────────────────────────────────
function logoutUser(): void {
    logActivity($_SESSION['user_id'] ?? null, 'LOGOUT', 'User logged out');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Activity Log ──────────────────────────────────────────────
function logActivity(?int $userId, string $action, string $desc = ''): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        getPDO()->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?,?,?,?)")
                ->execute([$userId, $action, $desc, $ip]);
    } catch (Exception $e) { /* silent */ }
}

// ── Current User ──────────────────────────────────────────────
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = getPDO()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
