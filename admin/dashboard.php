<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get admin information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests");
$stmt->execute();
$total_requests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE status = 'pending'");
$stmt->execute();
$pending_requests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE status = 'processing'");
$stmt->execute();
$processing_requests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE status = 'ready_for_pickup'");
$stmt->execute();
$ready_requests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type != 'admin'");
$stmt->execute();
$total_users = $stmt->fetchColumn();

// Get recent requests
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, u.full_name, u.email
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    ORDER BY dr.request_date DESC 
    LIMIT 10
");
$stmt->execute();
$recent_requests = $stmt->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?> - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a class="dropdown-item" href="manage_documents.php"><i class="fas fa-file-alt"></i> Manage Documents</a></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Welcome, <?php echo htmlspecialchars($admin['full_name']); ?>!</h2>
                <p class="text-muted">Manage document requests and system operations.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="manage_requests.php" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> Manage Requests
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $total_requests; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $pending_requests; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $processing_requests; ?></div>
                    <div class="stat-label">Processing</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $ready_requests; ?></div>
                    <div class="stat-label">Ready for Pickup</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Requests -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Recent Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requests)): ?>
                            <p class="text-muted text-center">No requests found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>User</th>
                                            <th>Document</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requests as $request): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($request['status']) {
                                                        case 'pending': $status_class = 'bg-warning'; break;
                                                        case 'processing': $status_class = 'bg-info'; break;
                                                        case 'approved': $status_class = 'bg-success'; break;
                                                        case 'denied': $status_class = 'bg-danger'; break;
                                                        case 'ready_for_pickup': $status_class = 'bg-success'; break;
                                                        case 'completed': $status_class = 'bg-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="update_status.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-edit"></i> Update
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="manage_requests.php" class="btn btn-outline-primary">View All Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="manage_requests.php" class="btn btn-primary">
                                <i class="fas fa-tasks"></i> Manage Requests
                            </a>
                            <a href="manage_users.php" class="btn btn-outline-primary">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="manage_documents.php" class="btn btn-outline-primary">
                                <i class="fas fa-file-alt"></i> Manage Documents
                            </a>
                            <a href="reports.php" class="btn btn-outline-primary">
                                <i class="fas fa-chart-bar"></i> Generate Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Total Users</small>
                                <div class="fw-bold"><?php echo $total_users; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Total Requests</small>
                                <div class="fw-bold"><?php echo $total_requests; ?></div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Pending</small>
                                <div class="fw-bold text-warning"><?php echo $pending_requests; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Ready</small>
                                <div class="fw-bold text-success"><?php echo $ready_requests; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>