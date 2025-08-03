<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lnhs_documents_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Session configuration
session_start();

// Site configuration
define('SITE_NAME', 'LNHS Documents Request Portal');
define('SITE_URL', 'http://localhost/lnhs-portal');
define('UPLOAD_PATH', 'uploads/');
define('ADMIN_EMAIL', 'admin@lnhs.edu.ph');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
?>