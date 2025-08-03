<?php
$page_title = "View Request";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

$request_id = intval($_GET['id'] ?? 0);

if ($request_id <= 0) {
    setFlashMessage('error', 'Invalid request ID.');
    header('Location: ' . (isAdmin() ? 'admin-dashboard.php' : 'my-requests.php'));
    exit();
}

$conn = getDBConnection();

// Get request details
$sql = "SELECT dr.*, dt.document_name, dt.description, dt.requirements, dt.processing_days,
               u.first_name, u.last_name, u.email, u.student_id, u.user_type, u.phone, u.address,
               processed_by_user.first_name as processed_by_name
        FROM document_requests dr 
        JOIN document_types dt ON dr.document_type_id = dt.id 
        JOIN users u ON dr.user_id = u.id 
        LEFT JOIN users processed_by_user ON dr.processed_by = processed_by_user.id
        WHERE dr.id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    setFlashMessage('error', 'Request not found.');
    header('Location: ' . (isAdmin() ? 'admin-dashboard.php' : 'my-requests.php'));
    exit();
}

// Check access permissions
$user_id = getCurrentUserId();
if (!isAdmin() && $request['user_id'] != $user_id) {
    setFlashMessage('error', 'You do not have permission to view this request.');
    header('Location: my-requests.php');
    exit();
}

// Get uploaded files
$uploaded_files = json_decode($request['uploaded_files'], true) ?? [];

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-file-alt me-2"></i>
                    Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?>
                </h2>
                <p class="text-muted mb-0">Document Request Details</p>
            </div>
            <div>
                <a href="<?php echo isAdmin() ? 'admin-dashboard.php' : 'my-requests.php'; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <?php if (isAdmin()): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                        <i class="fas fa-edit me-2"></i>Update Status
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Request Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Request Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Document Type</h6>
                        <p class="mb-3">
                            <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($request['description']); ?></small>
                        </p>
                        
                        <h6 class="text-muted">Purpose</h6>
                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($request['purpose'])); ?></p>
                        
                        <h6 class="text-muted">Processing Time</h6>
                        <p class="mb-3">
                            <span class="badge bg-info"><?php echo $request['processing_days']; ?> working days</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Request Date</h6>
                        <p class="mb-3"><?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?></p>
                        
                        <h6 class="text-muted">Preferred Release Date</h6>
                        <p class="mb-3">
                            <?php echo $request['preferred_release_date'] ? date('F j, Y', strtotime($request['preferred_release_date'])) : 'Not specified'; ?>
                        </p>
                        
                        <h6 class="text-muted">Total Fee</h6>
                        <p class="mb-3">
                            <strong class="text-success">₱<?php echo number_format($request['total_fee'], 2); ?></strong>
                            <br><small class="badge bg-<?php echo $request['payment_status'] === 'paid' ? 'success' : ($request['payment_status'] === 'waived' ? 'info' : 'warning'); ?>">
                                <?php echo ucfirst($request['payment_status']); ?>
                            </small>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($request['admin_notes'])): ?>
                    <hr>
                    <h6 class="text-muted">Admin Notes</h6>
                    <div class="alert alert-info">
                        <i class="fas fa-sticky-note me-2"></i>
                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Uploaded Files -->
        <?php if (!empty($uploaded_files)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-paperclip me-2"></i>Uploaded Documents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($uploaded_files as $file): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border">
                                    <div class="card-body text-center">
                                        <?php
                                        $file_ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                        $icon_class = in_array($file_ext, ['jpg', 'jpeg', 'png']) ? 'fa-image text-primary' : 
                                                     ($file_ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-alt text-secondary');
                                        ?>
                                        <i class="fas <?php echo $icon_class; ?> fa-3x mb-2"></i>
                                        <h6 class="card-title"><?php echo htmlspecialchars($file['original_name']); ?></h6>
                                        <?php if (file_exists($file['filepath'])): ?>
                                            <a href="<?php echo htmlspecialchars($file['filepath']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <?php if (isAdmin()): ?>
                                                <a href="<?php echo htmlspecialchars($file['filepath']); ?>" 
                                                   download class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-muted">File not available</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Requirements -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-list-ul me-2"></i>Required Documents
                </h5>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($request['requirements'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Status Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Status Timeline
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item <?php echo in_array($request['status'], ['pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed']) ? 'completed' : ''; ?>">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Request Submitted</h6>
                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></small>
                        </div>
                    </div>
                    
                    <?php if (in_array($request['status'], ['processing', 'approved', 'denied', 'ready_for_pickup', 'completed'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Under Review</h6>
                                <small class="text-muted">
                                    <?php echo $request['processed_date'] ? date('M j, Y g:i A', strtotime($request['processed_date'])) : 'In progress'; ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array($request['status'], ['approved', 'ready_for_pickup', 'completed'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Approved</h6>
                                <small class="text-muted">Document preparation started</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'denied'): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Denied</h6>
                                <small class="text-muted">Request was not approved</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array($request['status'], ['ready_for_pickup', 'completed'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Ready for Pickup</h6>
                                <small class="text-muted">Document is ready</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'completed'): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-dark"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Completed</h6>
                                <small class="text-muted">
                                    <?php echo $request['completed_date'] ? date('M j, Y g:i A', strtotime($request['completed_date'])) : 'Recently completed'; ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Student Information -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>Student Information
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong><br><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                <p><strong>Student ID:</strong><br><?php echo htmlspecialchars($request['student_id']); ?></p>
                <p><strong>Email:</strong><br><?php echo htmlspecialchars($request['email']); ?></p>
                <p><strong>Type:</strong><br><span class="badge bg-secondary"><?php echo ucfirst($request['user_type']); ?></span></p>
                <?php if ($request['phone']): ?>
                    <p><strong>Phone:</strong><br><?php echo htmlspecialchars($request['phone']); ?></p>
                <?php endif; ?>
                <?php if ($request['address']): ?>
                    <p class="mb-0"><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($request['address'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Current Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-flag me-2"></i>Current Status
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <span class="badge <?php echo getStatusBadgeClass($request['status']); ?> fs-5 px-3 py-2">
                        <?php echo formatStatus($request['status']); ?>
                    </span>
                </div>
                
                <?php if ($request['processed_by_name']): ?>
                    <p class="mb-0">
                        <small class="text-muted">
                            Processed by: <?php echo htmlspecialchars($request['processed_by_name']); ?>
                        </small>
                    </p>
                <?php endif; ?>
                
                <?php if ($request['status'] === 'ready_for_pickup'): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Ready for Pickup!</strong><br>
                        Please visit our office during business hours (8AM-5PM, Mon-Fri) to collect your document.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Admin Status Update Modal -->
<?php if (isAdmin()): ?>
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin-dashboard.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $request['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="denied" <?php echo $request['status'] === 'denied' ? 'selected' : ''; ?>>Denied</option>
                            <option value="ready_for_pickup" <?php echo $request['status'] === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Add any notes or comments..."><?php echo htmlspecialchars($request['admin_notes']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-item.completed .timeline-marker {
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
}
</style>

<?php require_once 'includes/footer.php'; ?>