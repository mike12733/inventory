<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    logActivity('logout', 'users', $_SESSION['user_id'], 'User logged out');
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit();
?>