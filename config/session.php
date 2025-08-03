<?php
// Session management functions

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

// Check if user is student or alumni
function isStudent() {
    return isLoggedIn() && in_array($_SESSION['user_type'], ['student', 'alumni']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user type
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Get current user info
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null
    ];
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Logout user
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>