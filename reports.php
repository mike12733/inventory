<?php
require_once 'config/database.php';
requireLogin();

try {
    // Equipment Summary
    $equipmentSummary = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'In Use' THEN 1 ELSE 0 END) as in_use,
            SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN status = 'Damaged' THEN 1 ELSE 0 END) as damaged,
            SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost
        FROM equipment
    ")->fetch();
    
    // Equipment by Category
    $categoryStats = $pdo->query("
        SELECT c.name, COUNT(e.id) as count, 
               AVG(e.purchase_price) as avg_price,
               SUM(e.purchase_price) as total_value
        FROM categories c
        LEFT JOIN equipment e ON c.id = e.category_id
        GROUP BY c.id, c.name
        ORDER BY count DESC
    ")->fetchAll();
    
    // Monthly Transaction Stats
    $monthlyStats = $pdo->query("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            transaction_type,
            COUNT(*) as count
        FROM inventory_transactions
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month, transaction_type
        ORDER BY month DESC, transaction_type
    ")->fetchAll();
    
    // Top Active Equipment
    $activeEquipment = $pdo->query("
        SELECT e.name, e.barcode, COUNT(it.id) as transaction_count,
               c.name as category_name
        FROM equipment e
        LEFT JOIN inventory_transactions it ON e.id = it.equipment_id
        LEFT JOIN categories c ON e.category_id = c.id
        GROUP BY e.id
        ORDER BY transaction_count DESC
        LIMIT 10
    ")->fetchAll();
    
    // Recent Activity
    $recentActivity = $pdo->query("
        SELECT it.*, e.name as equipment_name, a.full_name as admin_name
        FROM inventory_transactions it
        JOIN equipment e ON it.equipment_id = e.id
        JOIN admins a ON it.admin_id = a.id
        ORDER BY it.transaction_date DESC
        LIMIT 10
    ")->fetchAll();
    
    // Warranty Expiring Soon
    $warrantySoon = $pdo->query("
        SELECT name, barcode, warranty_expiry, 
               DATEDIFF(warranty_expiry, CURDATE()) as days_left
        FROM equipment
        WHERE warranty_expiry IS NOT NULL 
        AND warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY warranty_expiry ASC
    ")->fetchAll();
    
    // Equipment Value Analysis
    $valueStats = $pdo->query("
        SELECT 
            COUNT(*) as total_items,
            SUM(purchase_price) as total_value,
            AVG(purchase_price) as avg_value,
            MIN(purchase_price) as min_value,
            MAX(purchase_price) as max_value
        FROM equipment
        WHERE purchase_price IS NOT NULL
    ")->fetch();
    
} catch (Exception $e) {
    $error = "Error loading reports: " . $e->getMessage();
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Reports');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="equipment.php"><i class="fas fa-laptop"></i> Equipment</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
                    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
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
                
                <!-- Equipment Summary -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $equipmentSummary['total']; ?></div>
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
                                <div class="stat-number"><?php echo $equipmentSummary['available']; ?></div>
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
                                <div class="stat-number"><?php echo $equipmentSummary['in_use']; ?></div>
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
                                <div class="stat-number"><?php echo $valueStats['total_value'] ? '$' . number_format($valueStats['total_value'], 2) : 'N/A'; ?></div>
                                <div class="stat-label">Total Value</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                    <!-- Equipment Status Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Equipment Status Distribution</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Category Distribution Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Equipment by Category</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Equipment Value Analysis -->
                <?php if ($valueStats['total_items'] > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calculator"></i> Equipment Value Analysis</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div>
                                <h4>Total Items with Price Data</h4>
                                <p style="font-size: 24px; color: #667eea; margin: 0;"><?php echo $valueStats['total_items']; ?></p>
                            </div>
                            <div>
                                <h4>Average Value</h4>
                                <p style="font-size: 24px; color: #11998e; margin: 0;">$<?php echo number_format($valueStats['avg_value'], 2); ?></p>
                            </div>
                            <div>
                                <h4>Highest Value Item</h4>
                                <p style="font-size: 24px; color: #f093fb; margin: 0;">$<?php echo number_format($valueStats['max_value'], 2); ?></p>
                            </div>
                            <div>
                                <h4>Lowest Value Item</h4>
                                <p style="font-size: 24px; color: #fc466b; margin: 0;">$<?php echo number_format($valueStats['min_value'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Category Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tags"></i> Category Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Equipment Count</th>
                                        <th>Average Value</th>
                                        <th>Total Value</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryStats as $category): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                            <td><?php echo $category['count']; ?></td>
                                            <td><?php echo $category['avg_price'] ? '$' . number_format($category['avg_price'], 2) : '-'; ?></td>
                                            <td><?php echo $category['total_value'] ? '$' . number_format($category['total_value'], 2) : '-'; ?></td>
                                            <td>
                                                <?php 
                                                $percentage = $equipmentSummary['total'] > 0 ? ($category['count'] / $equipmentSummary['total']) * 100 : 0;
                                                echo number_format($percentage, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Two Column Layout -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Top Active Equipment -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-star"></i> Most Active Equipment</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($activeEquipment) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Category</th>
                                                <th>Transactions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activeEquipment as $equipment): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($equipment['name']); ?></strong><br>
                                                        <small><code><?php echo $equipment['barcode']; ?></code></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($equipment['category_name']); ?></td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo $equipment['transaction_count']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No transaction data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Warranty Alerts -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Warranty Expiring Soon</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($warrantySoon) > 0): ?>
                                <?php foreach ($warrantySoon as $warranty): ?>
                                    <div class="alert alert-warning" style="margin-bottom: 10px;">
                                        <strong><?php echo htmlspecialchars($warranty['name']); ?></strong><br>
                                        <small>Barcode: <?php echo $warranty['barcode']; ?></small><br>
                                        <small>Expires in <?php echo $warranty['days_left']; ?> days (<?php echo date('M d, Y', strtotime($warranty['warranty_expiry'])); ?>)</small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> No warranties expiring in the next 30 days.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Equipment</th>
                                        <th>Transaction</th>
                                        <th>User</th>
                                        <th>Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('M d, Y H:i', strtotime($activity['transaction_date'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['equipment_name']); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo $activity['transaction_type']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['user_name']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($activity['admin_name']); ?></td>
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
    
    <script>
        // Equipment Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Use', 'Maintenance', 'Damaged', 'Lost'],
                datasets: [{
                    data: [
                        <?php echo $equipmentSummary['available']; ?>,
                        <?php echo $equipmentSummary['in_use']; ?>,
                        <?php echo $equipmentSummary['maintenance']; ?>,
                        <?php echo $equipmentSummary['damaged']; ?>,
                        <?php echo $equipmentSummary['lost']; ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Category Bar Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { return '"' . htmlspecialchars($c['name']) . '"'; }, $categoryStats)); ?>],
                datasets: [{
                    label: 'Equipment Count',
                    data: [<?php echo implode(',', array_column($categoryStats, 'count')); ?>],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Print styles
        const printStyles = `
            @media print {
                .sidebar { display: none; }
                .header { display: none; }
                .main-content { margin-left: 0; }
                .btn { display: none; }
                .card { break-inside: avoid; }
            }
        `;
        const style = document.createElement('style');
        style.textContent = printStyles;
        document.head.appendChild(style);
    </script>
</body>
</html>