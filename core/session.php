<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Get the current script name (e.g., 'login.php')
$current_page = basename($_SERVER['PHP_SELF']);

// List of pages that DO NOT require login
$public_pages = ['login.php', 'register.php'];

// Auth Check Logic
if (!isset($_SESSION['user_id'])) {
    // If user is NOT logged in AND trying to access a protected page
    if (!in_array($current_page, $public_pages)) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
} 
// Conflict Fix: If user IS logged in but tries to go to Login/Register, send them to Dashboard
else {
    if (in_array($current_page, $public_pages)) {
        // Allow developers to force display of public pages even if logged-in using ?force=1
        // Or allow a quick logout via ?logout=1 to switch accounts and then show the public page
        $allow_force = isset($_GET['force']) && $_GET['force'] == '1';
        $do_logout = isset($_GET['logout']) && $_GET['logout'] == '1';
        if ($do_logout) {
            // Logout current session and continue to show the public page
            session_unset();
            session_destroy();
            session_start();
        } 
        // By commenting out the redirect below, a logged-in user (like an admin)
        // can visit the login page again to log in as a different user (like a student)
        // without needing to log out first. This is useful for testing.
        elseif (! $allow_force) { header("Location: " . BASE_URL . "index.php"); exit; }
    }
}

// Helper to determine color based on role string
function getRoleBadgeColor($roleName) {
    $colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
    $index = crc32($roleName) % count($colors);
    return 'text-bg-' . $colors[$index];
}
?>