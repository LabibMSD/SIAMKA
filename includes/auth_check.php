<?php
/**
 * Authentication Check Middleware
 * Include this at the top of protected pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kalau belum login, arahkan ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}
?>
