<?php
require_once 'config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get statistics for reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipment");
$total_equipment = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as available FROM equipment WHERE status = 'available'");
$available_equipment = $stmt->fetch()['available'];

$stmt = $pdo->query("SELECT COUNT(*) as in_use FROM equipment WHERE status = 'in_use'");
$in_use_equipment = $stmt->fetch()['in_use'];

$stmt = $pdo->query("SELECT COUNT(*) as maintenance FROM equipment WHERE status = 'maintenance'");
$maintenance_equipment = $stmt->fetch()['maintenance'];

$stmt = $pdo->query("SELECT COUNT(*) as damaged FROM equipment WHERE status = 'damaged'");
$damaged_equipment = $stmt->fetch()['damaged'];

$stmt = $pdo->query("SELECT COUNT(*) as lost FROM equipment WHERE status = 'lost'");
$lost_equipment = $stmt->fetch()['lost'];

// Get equipment by category
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM equipment WHERE category IS NOT NULL GROUP BY category ORDER BY count DESC");
$category_stats = $stmt->fetchAll();

// Get equipment by location
$stmt = $pdo->query("SELECT location, COUNT(*) as count FROM equipment WHERE location IS NOT NULL GROUP BY location ORDER BY count DESC");
$location_stats = $stmt->fetchAll();

// Get total value
$stmt = $pdo->query("SELECT SUM(purchase_price) as total_value FROM equipment WHERE purchase_price IS NOT NULL");
$total_value = $stmt->fetch()['total_value'] ?? 0;

// Get recent equipment
$stmt = $pdo->query("SELECT * FROM equipment ORDER BY created_at DESC LIMIT 10");
$recent_equipment = $stmt->fetchAll();

// Get equipment by status
$status_stats = [
    'Available' => $available_equipment,
    'In Use' => $in_use_equipment,
    'Maintenance' => $maintenance_equipment,
    'Damaged' => $damaged_equipment,
    'Lost' => $lost_equipment
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="reports.php">
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
                            <h4 class="mb-0">Reports & Analytics</h4>
                            <div class="d-flex align-items-center">
                                <button onclick="window.print()" class="btn btn-success me-2">
                                    <i class="fas fa-print me-1"></i>Print Report
                                </button>
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
                        <!-- Summary Statistics -->
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
                                <div class="card stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <h3><?php echo $available_equipment; ?></h3>
                                        <p class="mb-0">Available</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <h3><?php echo $in_use_equipment; ?></h3>
                                        <p class="mb-0">In Use</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
                                    <div class="card-body text-center">
                                        <i class="fas fa-money-bill fa-2x mb-2"></i>
                                        <h3>₱<?php echo number_format($total_value, 2); ?></h3>
                                        <p class="mb-0">Total Value</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charts Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-pie me-2"></i>Equipment by Status
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="statusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar me-2"></i>Equipment by Category
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="categoryChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detailed Reports -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-list me-2"></i>Equipment by Category
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Count</th>
                                                        <th>Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($category_stats as $category): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $category['count']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php echo round(($category['count'] / $total_equipment) * 100, 1); ?>%
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>Equipment by Location
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Location</th>
                                                        <th>Count</th>
                                                        <th>Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($location_stats as $location): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $location['count']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php echo round(($location['count'] / $total_equipment) * 100, 1); ?>%
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
                        
                        <!-- Recent Equipment -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-clock me-2"></i>Recently Added Equipment
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Status</th>
                                                        <th>Added Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_equipment as $equipment): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($equipment['equipment_code']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($equipment['category']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_class = 'bg-secondary';
                                                            switch ($equipment['status']) {
                                                                case 'available': $status_class = 'bg-success'; break;
                                                                case 'in_use': $status_class = 'bg-warning'; break;
                                                                case 'maintenance': $status_class = 'bg-info'; break;
                                                                case 'damaged': $status_class = 'bg-danger'; break;
                                                                case 'lost': $status_class = 'bg-dark'; break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $equipment['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M d, Y', strtotime($equipment['created_at'])); ?>
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
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($status_stats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_stats)); ?>,
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
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

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($category_stats, 'category')); ?>,
                datasets: [{
                    label: 'Equipment Count',
                    data: <?php echo json_encode(array_column($category_stats, 'count')); ?>,
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
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>