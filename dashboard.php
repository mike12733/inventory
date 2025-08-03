<?php
$page_title = "Dashboard";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

// Only students and alumni should access this dashboard
if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$current_user = getCurrentUser();

// Get user statistics
$conn = getDBConnection();

// Count total requests
$sql = "SELECT COUNT(*) as total FROM document_requests WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$total_requests = $stmt->fetch()['total'];

// Count pending requests
$sql = "SELECT COUNT(*) as pending FROM document_requests WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$pending_requests = $stmt->fetch()['pending'];

// Count processing requests
$sql = "SELECT COUNT(*) as processing FROM document_requests WHERE user_id = ? AND status IN ('processing', 'approved')";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$processing_requests = $stmt->fetch()['processing'];

// Count completed requests
$sql = "SELECT COUNT(*) as completed FROM document_requests WHERE user_id = ? AND status IN ('ready_for_pickup', 'completed')";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$completed_requests = $stmt->fetch()['completed'];

// Get recent requests
$sql = "SELECT dr.*, dt.document_name, dt.fee 
        FROM document_requests dr 
        JOIN document_types dt ON dr.document_type_id = dt.id 
        WHERE dr.user_id = ? 
        ORDER BY dr.request_date DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$recent_requests = $stmt->fetchAll();

// Get recent notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$recent_notifications = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="page-header text-center">
            <h1 class="display-4 mb-2">
                <i class="fas fa-tachometer-alt me-3"></i>Dashboard
            </h1>
            <p class="lead mb-0">Welcome back, <?php echo htmlspecialchars($current_user['first_name']); ?>!</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <h2 class="mb-2"><?php echo $total_requests; ?></h2>
                <h6 class="mb-0">Total Requests</h6>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h2 class="mb-2"><?php echo $pending_requests; ?></h2>
                <h6 class="mb-0">Pending</h6>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-cog fa-3x mb-3"></i>
                <h2 class="mb-2"><?php echo $processing_requests; ?></h2>
                <h6 class="mb-0">Processing</h6>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h2 class="mb-2"><?php echo $completed_requests; ?></h2>
                <h6 class="mb-0">Completed</h6>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="request-document.php" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            <div>New Document Request</div>
                            <small>Submit a new request</small>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="my-requests.php" class="btn btn-info btn-lg w-100">
                            <i class="fas fa-list me-2"></i>
                            <div>View All Requests</div>
                            <small>Track your submissions</small>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="profile.php" class="btn btn-secondary btn-lg w-100">
                            <i class="fas fa-user-edit me-2"></i>
                            <div>Update Profile</div>
                            <small>Manage your information</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Requests and Notifications -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Requests
                </h5>
                <a href="my-requests.php" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_requests): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                                            <br><small class="text-muted">₱<?php echo number_format($request['fee'], 2); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($request['status']); ?>">
                                                <?php echo formatStatus($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                                        <td>
                                            <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No requests yet</h5>
                        <p class="text-muted">Start by creating your first document request</p>
                        <a href="request-document.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-bell me-2"></i>Notifications
                </h5>
                <a href="notifications.php" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_notifications): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_notifications as $notification): ?>
                            <div class="list-group-item <?php echo !$notification['is_read'] ? 'bg-light border-start border-primary border-3' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('M j', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 100)); ?>...</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No notifications</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Available Documents Info -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Available Documents & Fees
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $document_types = getDocumentTypes();
                    foreach ($document_types as $doc): 
                    ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-certificate fa-2x text-success mb-2"></i>
                                    <h6 class="card-title"><?php echo htmlspecialchars($doc['document_name']); ?></h6>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($doc['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-success">₱<?php echo number_format($doc['fee'], 2); ?></span>
                                        <small class="text-muted"><?php echo $doc['processing_days']; ?> days</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="request-document.php" class="btn btn-success btn-lg">
                        <i class="fas fa-file-plus me-2"></i>Request Document Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>