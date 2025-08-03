<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);
    
    $stmt = $pdo->prepare("UPDATE document_requests SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt->execute([$new_status, $admin_notes, $request_id])) {
        // Get user info for notification
        $stmt = $pdo->prepare("SELECT u.id, u.full_name FROM document_requests dr JOIN users u ON dr.user_id = u.id WHERE dr.id = ?");
        $stmt->execute([$request_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Create notification
            $notification_title = 'Request Status Updated';
            $notification_message = "Your document request #" . str_pad($request_id, 4, '0', STR_PAD_LEFT) . " status has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $notification_title, $notification_message, 'portal']);
        }
        
        // Log admin action
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, 'update_status', "Updated request #$request_id status to $new_status", $_SERVER['REMOTE_ADDR']]);
        
        $success = 'Request status updated successfully!';
    } else {
        $error = 'Failed to update request status.';
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$document_filter = isset($_GET['document']) ? $_GET['document'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if ($document_filter) {
    $where_conditions[] = "dr.document_type_id = ?";
    $params[] = $document_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(dr.request_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(dr.request_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query = "
    SELECT dr.*, dt.name as document_name, dt.fee, u.full_name, u.email, u.contact_number
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    $where_clause
    ORDER BY dr.request_date DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    $where_clause
";

$count_params = array_slice($params, 0, -2);
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_requests = $stmt->fetchColumn();
$total_pages = ceil($total_requests / $limit);

// Get document types for filter
$stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
$stmt->execute();
$document_types = $stmt->fetchAll();

$success = isset($success) ? $success : '';
$error = isset($error) ? $error : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - <?php echo SITE_NAME; ?></title>
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
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?> - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tasks"></i> Manage Requests</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filter-section">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filters</h5>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="denied" <?php echo $status_filter == 'denied' ? 'selected' : ''; ?>>Denied</option>
                                    <option value="ready_for_pickup" <?php echo $status_filter == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="document" class="form-label">Document Type</label>
                                <select class="form-control" id="document" name="document">
                                    <option value="">All Documents</option>
                                    <?php foreach ($document_types as $doc_type): ?>
                                        <option value="<?php echo $doc_type['id']; ?>" <?php echo $document_filter == $doc_type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doc_type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <a href="manage_requests.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Document Requests 
                            <span class="badge bg-secondary"><?php echo $total_requests; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No requests found</h5>
                                <p class="text-muted">No requests match your current filters.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>User</th>
                                            <th>Document</th>
                                            <th>Request Date</th>
                                            <th>Preferred Date</th>
                                            <th>Status</th>
                                            <th>Fee</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?></td>
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
                                                <td>₱<?php echo number_format($request['fee'], 2); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="update_status.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&document=<?php echo $document_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&document=<?php echo $document_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&document=<?php echo $document_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>