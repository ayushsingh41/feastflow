<?php
// FeastFlow — Entry Point
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $redirect = isAdmin() ? '/admin/dashboard.php' : '/customer/dashboard.php';
    header('Location: ' . APP_URL . $redirect);
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;
