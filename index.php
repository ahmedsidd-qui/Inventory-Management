<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

// Redirect to appropriate dashboard if logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'owner') {
        header('Location: ' . url('owner/dashboard.php'));
    } else {
        header('Location: ' . url('admin/dashboard.php'));
    }
    exit();
}

// Otherwise redirect to login
header('Location: ' . url('login.php'));
exit();
?>
