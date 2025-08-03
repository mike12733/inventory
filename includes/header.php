<?php
require_once 'config/session.php';
require_once 'includes/functions.php';
$current_user = getCurrentUser();
$unread_count = isLoggedIn() ? getUnreadNotificationsCount(getCurrentUserId()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>LNHS Documents Request Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --accent-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            background: linear-gradient(135deg, var(--primary-color), #1e3a5f);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1e3a5f);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a5f, var(--primary-color));
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .badge {
            border-radius: 20px;
            padding: 8px 12px;
            font-weight: 500;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar {
            min-height: calc(100vh - 76px);
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,.1);
        }
        
        .sidebar .nav-link {
            color: #495057;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), #1e3a5f);
            color: white;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
        }
        
        .status-pending { color: #ffc107; }
        .status-processing { color: #17a2b8; }
        .status-approved { color: #28a745; }
        .status-denied { color: #dc3545; }
        .status-ready { color: #007bff; }
        .status-completed { color: #6c757d; }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,.1);
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #1e3a5f);
            color: white;
            padding: 40px 0;
            margin: -20px -15px 30px -15px;
            border-radius: 0 0 20px 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo isLoggedIn() ? 'dashboard.php' : 'index.php'; ?>">
                <i class="fas fa-graduation-cap me-2"></i>LNHS Documents Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        
                        <?php if (isStudent()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="request-document.php">
                                    <i class="fas fa-file-alt me-1"></i>New Request
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my-requests.php">
                                    <i class="fas fa-list me-1"></i>My Requests
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin-dashboard.php">
                                    <i class="fas fa-cogs me-1"></i>Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell me-1"></i>Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                <li><h6 class="dropdown-header">Recent Notifications</h6></li>
                                <div id="notifications-list">
                                    <!-- Notifications will be loaded here -->
                                </div>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($current_user['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="change-password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
        <div class="container-fluid mt-3">
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Page Content -->
    <div class="container-fluid px-4"><?antml:parameter>