<?php
/**
 * Session Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set("session.cookie_httponly", 1);
    ini_set("session.use_only_cookies", 1);
    ini_set("session.cookie_samesite", "Strict");
    
    // Start session
    session_start();
    
    // Set session timeout
    if (isset($_SESSION["last_activity"])) {
        if (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "modules/auth/login.php?timeout=1");
            exit;
        }
    }
    
    $_SESSION["last_activity"] = time();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION["created"])) {
        $_SESSION["created"] = time();
    } else if (time() - $_SESSION["created"] > 1800) {
        session_regenerate_id(true);
        $_SESSION["created"] = time();
    }
}
?>