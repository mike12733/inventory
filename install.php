<?php
// LNHS Documents Request Portal - Installation Script
// This script helps you set up the system

echo "<!DOCTYPE html>
<html>
<head>
    <title>LNHS Documents Request Portal - Installation</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 800px; }
        .step { margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='text-center mb-4'>LNHS Documents Request Portal - Installation</h1>";

// Check PHP version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
echo "<div class='step " . ($php_ok ? 'success' : 'error') . "'>
    <h4>Step 1: PHP Version Check</h4>
    <p><strong>Current PHP Version:</strong> $php_version</p>
    <p><strong>Required:</strong> PHP 7.4 or higher</p>
    <p><strong>Status:</strong> " . ($php_ok ? '✅ PASSED' : '❌ FAILED') . "</p>
</div>";

// Check required extensions
$extensions = ['pdo', 'pdo_mysql', 'session'];
$ext_ok = true;
$ext_status = [];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $ext_status[$ext] = $loaded;
    if (!$loaded) $ext_ok = false;
}

echo "<div class='step " . ($ext_ok ? 'success' : 'error') . "'>
    <h4>Step 2: PHP Extensions Check</h4>";
foreach ($ext_status as $ext => $loaded) {
    echo "<p><strong>$ext:</strong> " . ($loaded ? '✅ Loaded' : '❌ Not Loaded') . "</p>";
}
echo "<p><strong>Status:</strong> " . ($ext_ok ? '✅ PASSED' : '❌ FAILED') . "</p>
</div>";

// Check file permissions
$uploads_writable = is_writable('uploads/');
$config_writable = is_writable('config/');

echo "<div class='step " . ($uploads_writable && $config_writable ? 'success' : 'warning') . "'>
    <h4>Step 3: File Permissions Check</h4>
    <p><strong>uploads/ directory:</strong> " . ($uploads_writable ? '✅ Writable' : '❌ Not Writable') . "</p>
    <p><strong>config/ directory:</strong> " . ($config_writable ? '✅ Writable' : '❌ Not Writable') . "</p>
    <p><strong>Status:</strong> " . ($uploads_writable && $config_writable ? '✅ PASSED' : '⚠️ WARNING') . "</p>
</div>";

// Test database connection
$db_ok = false;
$db_error = '';
if ($php_ok && $ext_ok) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=lnhs_documents_portal", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_ok = true;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

echo "<div class='step " . ($db_ok ? 'success' : 'error') . "'>
    <h4>Step 4: Database Connection Test</h4>";
if ($db_ok) {
    echo "<p><strong>Status:</strong> ✅ Connected successfully</p>";
} else {
    echo "<p><strong>Status:</strong> ❌ Connection failed</p>
    <p><strong>Error:</strong> $db_error</p>
    <p><strong>Solution:</strong> Make sure MySQL is running and the database 'lnhs_documents_portal' exists.</p>";
}
echo "</div>";

// Overall status
$overall_ok = $php_ok && $ext_ok && $db_ok;
echo "<div class='step " . ($overall_ok ? 'success' : 'error') . "'>
    <h4>Installation Summary</h4>
    <p><strong>Overall Status:</strong> " . ($overall_ok ? '✅ READY TO USE' : '❌ NEEDS ATTENTION') . "</p>";

if ($overall_ok) {
    echo "<div class='alert alert-success'>
        <h5>🎉 Installation Complete!</h5>
        <p>Your LNHS Documents Request Portal is ready to use.</p>
        <p><strong>Default Admin Login:</strong></p>
        <ul>
            <li>Username: admin</li>
            <li>Password: password</li>
        </ul>
        <p><strong>Important:</strong> Change the default admin password after first login!</p>
        <a href='index.php' class='btn btn-success'>Go to Login Page</a>
    </div>";
} else {
    echo "<div class='alert alert-danger'>
        <h5>⚠️ Installation Issues Found</h5>
        <p>Please fix the issues above before using the system.</p>
        <h6>Common Solutions:</h6>
        <ul>
            <li>Update PHP to version 7.4 or higher</li>
            <li>Enable required PHP extensions (pdo, pdo_mysql, session)</li>
            <li>Create the database 'lnhs_documents_portal' in phpMyAdmin</li>
            <li>Import the database.sql file</li>
            <li>Set proper file permissions (755 for directories, 644 for files)</li>
        </ul>
    </div>";
}

echo "</div>

<div class='step'>
    <h4>Next Steps</h4>
    <ol>
        <li>If installation is successful, click 'Go to Login Page' above</li>
        <li>Login with the default admin credentials</li>
        <li>Change the admin password immediately</li>
        <li>Configure email settings in config/database.php (optional)</li>
        <li>Add your school's document types in the admin panel</li>
        <li>Start accepting document requests from students and alumni</li>
    </ol>
</div>

<div class='step'>
    <h4>Support</h4>
    <p>If you encounter any issues:</p>
    <ul>
        <li>Check the README.md file for detailed instructions</li>
        <li>Verify all requirements are met</li>
        <li>Check your web server error logs</li>
        <li>Ensure XAMPP/WAMP is properly configured</li>
    </ul>
</div>

</div>
</body>
</html>";
?>