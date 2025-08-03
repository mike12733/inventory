<?php
require_once 'config/database.php';
requireLogin();

// Get dashboard statistics
try {
    // Total equipment count
    $totalEquipmentStmt = $pdo->query("SELECT COUNT(*) as total FROM equipment");
    $totalEquipment = $totalEquipmentStmt->fetch()['total'];
    
    // Available equipment count
    $availableStmt = $pdo->query("SELECT COUNT(*) as available FROM equipment WHERE status = 'Available'");
    $availableEquipment = $availableStmt->fetch()['available'];
    
    // In use equipment count
    $inUseStmt = $pdo->query("SELECT COUNT(*) as in_use FROM equipment WHERE status = 'In Use'");
    $inUseEquipment = $inUseStmt->fetch()['in_use'];
    
    // Maintenance equipment count
    $maintenanceStmt = $pdo->query("SELECT COUNT(*) as maintenance FROM equipment WHERE status = 'Maintenance'");
    $maintenanceEquipment = $maintenanceStmt->fetch()['maintenance'];
    
    // Recent activities
    $recentActivitiesStmt = $pdo->prepare("
        SELECT al.*, a.full_name 
        FROM admin_logs al 
        JOIN admins a ON al.admin_id = a.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $recentActivitiesStmt->execute();
    $recentActivities = $recentActivitiesStmt->fetchAll();
    
    // Low stock alerts (equipment with issues)
    $alertsStmt = $pdo->query("
        SELECT * FROM equipment 
        WHERE status IN ('Damaged', 'Lost') 
        ORDER BY updated_at DESC 
        LIMIT 5
    ");
    $alerts = $alertsStmt->fetchAll();
    
    // Recent transactions
    $transactionsStmt = $pdo->prepare("
        SELECT it.*, e.name as equipment_name, a.full_name as admin_name
        FROM inventory_transactions it
        JOIN equipment e ON it.equipment_id = e.id
        JOIN admins a ON it.admin_id = a.id
        ORDER BY it.transaction_date DESC
        LIMIT 8
    ");
    $transactionsStmt->execute();
    $transactions = $transactionsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Log dashboard access
logActivity($pdo, $_SESSION['admin_id'], 'Accessed Dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-boxes"></i> Inventory System</h3>
                <small>Management Panel</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="equipment.php"><i class="fas fa-laptop"></i> Equipment</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="barcode_generator.php"><i class="fas fa-barcode"></i> Barcode Generator</a></li>
                <li><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <span class="date"><i class="fas fa-calendar"></i> <?php echo date('M d, Y'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $totalEquipment; ?></div>
                                <div class="stat-label">Total Equipment</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $availableEquipment; ?></div>
                                <div class="stat-label">Available</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $inUseEquipment; ?></div>
                                <div class="stat-label">In Use</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-user-cog"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $maintenanceEquipment; ?></div>
                                <div class="stat-label">Maintenance</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <a href="equipment.php?action=add" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Equipment
                            </a>
                            <a href="barcode_generator.php" class="btn btn-info">
                                <i class="fas fa-barcode"></i> Generate Barcode
                            </a>
                            <a href="transactions.php?action=add" class="btn btn-warning">
                                <i class="fas fa-exchange-alt"></i> New Transaction
                            </a>
                            <a href="reports.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Recent Transactions -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-exchange-alt"></i> Recent Transactions</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($transactions) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($transaction['equipment_name']); ?></td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo $transaction['transaction_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="transactions.php" class="btn btn-primary">View All Transactions</a>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #666; padding: 20px;">
                                    <i class="fas fa-info-circle"></i> No recent transactions found.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- System Alerts -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> System Alerts</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($alerts) > 0): ?>
                                <?php foreach ($alerts as $alert): ?>
                                    <div class="alert alert-<?php echo $alert['status'] == 'Damaged' ? 'warning' : 'danger'; ?>" style="margin-bottom: 10px;">
                                        <strong><?php echo htmlspecialchars($alert['name']); ?></strong><br>
                                        <small>Status: <?php echo $alert['status']; ?> | Last updated: <?php echo date('M d, Y', strtotime($alert['updated_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="equipment.php?status=Damaged,Lost" class="btn btn-warning">View All Alerts</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> All equipment is in good condition!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Activities</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentActivities) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Table</th>
                                            <th>Date</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                <td><?php echo $activity['table_affected'] ?: '-'; ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                                <td><?php echo $activity['ip_address'] ?: '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="admin_logs.php" class="btn btn-primary">View All Logs</a>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; color: #666; padding: 20px;">
                                <i class="fas fa-info-circle"></i> No recent activities found.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Add loading animation to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.href || this.href.includes('#')) return;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            });
        });
    </script>
</body>
</html>