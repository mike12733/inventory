<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] == 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's requests
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee 
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    WHERE dr.user_id = ? 
    ORDER BY dr.request_date DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$success = '';
$error = '';

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
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
        .notification-item {
            border-left: 4px solid #667eea;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
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
                <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                <p class="text-muted">Manage your document requests and track their status.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="request_document.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Request
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-file-alt"></i>
                        </h5>
                        <h3 class="card-text"><?php echo count($requests); ?></h3>
                        <p class="card-text text-muted">Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="fas fa-clock"></i>
                        </h5>
                        <h3 class="card-text"><?php echo count(array_filter($requests, function($r) { return $r['status'] == 'pending'; })); ?></h3>
                        <p class="card-text text-muted">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <i class="fas fa-cogs"></i>
                        </h5>
                        <h3 class="card-text"><?php echo count(array_filter($requests, function($r) { return $r['status'] == 'processing'; })); ?></h3>
                        <p class="card-text text-muted">Processing</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <i class="fas fa-check-circle"></i>
                        </h5>
                        <h3 class="card-text"><?php echo count(array_filter($requests, function($r) { return $r['status'] == 'ready_for_pickup'; })); ?></h3>
                        <p class="card-text text-muted">Ready</p>
                    </div>
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
                        <?php if (empty($requests)): ?>
                            <p class="text-muted text-center">No requests yet. <a href="request_document.php">Make your first request</a></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Document</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($requests, 0, 5) as $request): ?>
                                            <tr>
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
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($requests) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="my_requests.php" class="btn btn-outline-primary">View All Requests</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted text-center">No new notifications</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>