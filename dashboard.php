<?php
require_once 'config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipment");
$total_equipment = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as available FROM equipment WHERE status = 'available'");
$available_equipment = $stmt->fetch()['available'];

$stmt = $pdo->query("SELECT COUNT(*) as in_use FROM equipment WHERE status = 'in_use'");
$in_use_equipment = $stmt->fetch()['in_use'];

$stmt = $pdo->query("SELECT COUNT(*) as maintenance FROM equipment WHERE status = 'maintenance'");
$maintenance_equipment = $stmt->fetch()['maintenance'];

// Get recent activities
$stmt = $pdo->query("SELECT al.*, u.username FROM activity_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC LIMIT 10");
$recent_activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.available {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .stat-card.in-use {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .stat-card.maintenance {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-boxes fa-2x mb-2"></i>
                        <h5>Inventory System</h5>
                        <small>Equipment Management</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="equipment.php">
                            <i class="fas fa-boxes me-2"></i>Equipment
                        </a>
                        <a class="nav-link" href="add_equipment.php">
                            <i class="fas fa-plus me-2"></i>Add Equipment
                        </a>
                        <a class="nav-link" href="barcode_generator.php">
                            <i class="fas fa-barcode me-2"></i>Barcode Generator
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="activity_logs.php">
                            <i class="fas fa-history me-2"></i>Activity Logs
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Top Navbar -->
                    <nav class="navbar navbar-expand-lg">
                        <div class="container-fluid">
                            <h4 class="mb-0">Dashboard</h4>
                            <div class="d-flex align-items-center">
                                <span class="me-3">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                </span>
                                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a>
                            </div>
                        </div>
                    </nav>
                    
                    <div class="p-4">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-boxes fa-2x mb-2"></i>
                                        <h3><?php echo $total_equipment; ?></h3>
                                        <p class="mb-0">Total Equipment</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card available">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <h3><?php echo $available_equipment; ?></h3>
                                        <p class="mb-0">Available</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card in-use">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <h3><?php echo $in_use_equipment; ?></h3>
                                        <p class="mb-0">In Use</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card maintenance">
                                    <div class="card-body text-center">
                                        <i class="fas fa-tools fa-2x mb-2"></i>
                                        <h3><?php echo $maintenance_equipment; ?></h3>
                                        <p class="mb-0">Maintenance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <a href="add_equipment.php" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i>Add Equipment
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="equipment.php" class="btn btn-info w-100">
                                                    <i class="fas fa-list me-2"></i>View All Equipment
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="barcode_generator.php" class="btn btn-success w-100">
                                                    <i class="fas fa-barcode me-2"></i>Generate Barcode
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="reports.php" class="btn btn-warning w-100">
                                                    <i class="fas fa-chart-bar me-2"></i>Generate Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>User</th>
                                                        <th>Action</th>
                                                        <th>Details</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_activities as $activity): ?>
                                                    <tr>
                                                        <td>
                                                            <i class="fas fa-user me-2"></i>
                                                            <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary">
                                                                <?php echo htmlspecialchars($activity['action']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($activity['details'] ?? ''); ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>