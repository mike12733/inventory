<?php
require_once 'config/database.php';
requireLogin();

// Get filters
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$admin_filter = $_GET['admin'] ?? '';

// Build where conditions
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(al.action LIKE ? OR al.table_affected LIKE ? OR a.full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($action_filter) {
    $whereConditions[] = "al.action = ?";
    $params[] = $action_filter;
}

if ($date_from) {
    $whereConditions[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

if ($admin_filter) {
    $whereConditions[] = "al.admin_id = ?";
    $params[] = $admin_filter;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get logs with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM admin_logs al 
        JOIN admins a ON al.admin_id = a.id 
        $whereClause
    ");
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetch()['total'];
    $totalPages = ceil($totalLogs / $perPage);
    
    // Get logs
    $logsStmt = $pdo->prepare("
        SELECT al.*, a.full_name, a.email
        FROM admin_logs al 
        JOIN admins a ON al.admin_id = a.id 
        $whereClause 
        ORDER BY al.created_at DESC 
        LIMIT $perPage OFFSET $offset
    ");
    $logsStmt->execute($params);
    $logs = $logsStmt->fetchAll();
    
    // Get unique actions for filter
    $actionsStmt = $pdo->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions = $actionsStmt->fetchAll();
    
    // Get admins for filter
    $adminsStmt = $pdo->query("SELECT id, full_name FROM admins ORDER BY full_name");
    $admins = $adminsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading logs: " . $e->getMessage();
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Admin Logs');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Activity Logs - Inventory System</title>
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="equipment.php"><i class="fas fa-laptop"></i> Equipment</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="barcode_generator.php"><i class="fas fa-barcode"></i> Barcode Generator</a></li>
                <li><a href="admin_logs.php" class="active"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1><i class="fas fa-history"></i> Admin Activity Logs</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <span class="badge badge-info"><?php echo $totalLogs; ?> total logs</span>
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
                
                <!-- Search and Filter -->
                <div class="search-filter">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search actions, tables, or admin names..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label for="action">Action</label>
                                <select id="action" name="action" class="form-control">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $action_filter == $action['action'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action['action']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="admin">Admin</label>
                                <select id="admin" name="admin" class="form-control">
                                    <option value="">All Admins</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo $admin_filter == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_from">Date From</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">Date To</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="admin_logs.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Activity Logs -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Activity Logs (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($logs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Admin</th>
                                            <th>Action</th>
                                            <th>Table</th>
                                            <th>Record ID</th>
                                            <th>IP Address</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($log['created_at'])); ?></small><br>
                                                    <small style="color: #666;"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($log['email']); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $actionClass = 'secondary';
                                                    if (strpos($log['action'], 'Login') !== false) $actionClass = 'success';
                                                    elseif (strpos($log['action'], 'Logout') !== false) $actionClass = 'warning';
                                                    elseif (strpos($log['action'], 'Added') !== false) $actionClass = 'success';
                                                    elseif (strpos($log['action'], 'Updated') !== false) $actionClass = 'info';
                                                    elseif (strpos($log['action'], 'Deleted') !== false) $actionClass = 'danger';
                                                    elseif (strpos($log['action'], 'Failed') !== false) $actionClass = 'danger';
                                                    ?>
                                                    <span class="badge badge-<?php echo $actionClass; ?>">
                                                        <?php echo htmlspecialchars($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $log['table_affected'] ?: '-'; ?></td>
                                                <td><?php echo $log['record_id'] ?: '-'; ?></td>
                                                <td>
                                                    <small><?php echo $log['ip_address'] ?: '-'; ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($log['old_values'] || $log['new_values']): ?>
                                                        <button class="btn btn-info btn-sm" onclick="showLogDetails(<?php echo $log['id']; ?>)">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div style="margin-top: 20px; text-align: center;">
                                    <div style="display: inline-flex; gap: 5px; align-items: center;">
                                        <?php if ($page > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                               class="btn btn-<?php echo $i == $page ? 'primary' : 'secondary'; ?> btn-sm">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary btn-sm">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 10px; color: #666;">
                                        Showing <?php echo (($page - 1) * $perPage) + 1; ?> to <?php echo min($page * $perPage, $totalLogs); ?> of <?php echo $totalLogs; ?> logs
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No Logs Found</h3>
                                <p>No activity logs match your search criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Log Details</h3>
                <button class="close" onclick="document.getElementById('logDetailsModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Log details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        function showLogDetails(logId) {
            fetch(`get_log_details.php?id=${logId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('logDetailsContent').innerHTML = html;
                    document.getElementById('logDetailsModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching log details:', error);
                    alert('Error loading log details');
                });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('logDetailsModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Set default date range to last 30 days if no filters are applied
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('date_from') && !urlParams.has('date_to') && !urlParams.has('search') && !urlParams.has('action')) {
                const today = new Date();
                const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                
                document.getElementById('date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
                document.getElementById('date_to').value = today.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>