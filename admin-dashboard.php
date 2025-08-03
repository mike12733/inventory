<?php
$page_title = "Admin Dashboard";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireAdmin();

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $request_id = intval($_POST['request_id']);
        $new_status = sanitizeInput($_POST['new_status']);
        $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
        
        $valid_statuses = ['pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed'];
        
        if (in_array($new_status, $valid_statuses)) {
            $sql = "UPDATE document_requests SET status = ?, admin_notes = ?, processed_by = ?, processed_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([$new_status, $admin_notes, getCurrentUserId(), $request_id])) {
                // Get request details for notification
                $req_sql = "SELECT dr.*, dt.document_name, u.first_name, u.email 
                           FROM document_requests dr 
                           JOIN document_types dt ON dr.document_type_id = dt.id 
                           JOIN users u ON dr.user_id = u.id 
                           WHERE dr.id = ?";
                $req_stmt = $conn->prepare($req_sql);
                $req_stmt->execute([$request_id]);
                $request_data = $req_stmt->fetch();
                
                if ($request_data) {
                    $status_messages = [
                        'processing' => 'Your document request is now being processed.',
                        'approved' => 'Your document request has been approved and is being prepared.',
                        'denied' => 'Your document request has been denied. ' . (!empty($admin_notes) ? 'Reason: ' . $admin_notes : ''),
                        'ready_for_pickup' => 'Your document is ready for pickup! Please visit our office during business hours.',
                        'completed' => 'Your document request has been completed. Thank you!'
                    ];
                    
                    $message = $status_messages[$new_status] ?? "Your request status has been updated to " . formatStatus($new_status);
                    sendNotification($request_data['user_id'], 'Request Status Updated', $message, 'info', $request_id);
                }
                
                setFlashMessage('success', 'Request status updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update request status.');
            }
        } else {
            setFlashMessage('error', 'Invalid status selected.');
        }
        
        header('Location: admin-dashboard.php');
        exit();
    }
}

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$date_filter = sanitizeInput($_GET['date'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(dr.request_date) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(dt.document_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR dr.purpose LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_count,
    SUM(total_fee) as total_revenue
    FROM document_requests";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Get requests with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total 
              FROM document_requests dr 
              JOIN document_types dt ON dr.document_type_id = dt.id 
              JOIN users u ON dr.user_id = u.id 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_requests = $count_stmt->fetch()['total'];
$total_pages = ceil($total_requests / $per_page);

$sql = "SELECT dr.*, dt.document_name, dt.processing_days,
               u.first_name, u.last_name, u.email, u.student_id, u.user_type,
               processed_by_user.first_name as processed_by_name
        FROM document_requests dr 
        JOIN document_types dt ON dr.document_type_id = dt.id 
        JOIN users u ON dr.user_id = u.id 
        LEFT JOIN users processed_by_user ON dr.processed_by = processed_by_user.id
        $where_clause 
        ORDER BY 
            CASE 
                WHEN dr.status = 'pending' THEN 1
                WHEN dr.status = 'processing' THEN 2
                WHEN dr.status = 'approved' THEN 3
                ELSE 4
            END,
            dr.request_date DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="page-header text-center">
            <h1 class="display-4 mb-2">
                <i class="fas fa-cogs me-3"></i>Admin Dashboard
            </h1>
            <p class="lead mb-0">Manage document requests and system operations</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-2x mb-2"></i>
                <h4 class="mb-1"><?php echo $stats['total_requests']; ?></h4>
                <small>Total Requests</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h4 class="mb-1"><?php echo $stats['pending_count']; ?></h4>
                <small>Pending</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-cog fa-2x mb-2"></i>
                <h4 class="mb-1"><?php echo $stats['processing_count']; ?></h4>
                <small>Processing</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-check fa-2x mb-2"></i>
                <h4 class="mb-1"><?php echo $stats['approved_count']; ?></h4>
                <small>Approved</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-hand-paper fa-2x mb-2"></i>
                <h4 class="mb-1"><?php echo $stats['ready_count']; ?></h4>
                <small>Ready</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-dark text-white h-100">
            <div class="card-body text-center">
                <i class="fas fa-money-bill fa-2x mb-2"></i>
                <h4 class="mb-1">₱<?php echo number_format($stats['total_revenue'], 0); ?></h4>
                <small>Total Revenue</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filters & Search
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, email, document type...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="denied" <?php echo $status_filter === 'denied' ? 'selected' : ''; ?>>Denied</option>
                            <option value="ready_for_pickup" <?php echo $status_filter === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="admin-dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                            <button type="button" class="btn btn-success" onclick="exportData()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Document Requests (<?php echo $total_requests; ?>)
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($requests): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Student Info</th>
                                    <th>Document</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr class="<?php echo $request['status'] === 'pending' ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong>#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            <br><small class="text-muted">₱<?php echo number_format($request['total_fee'], 2); ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                <br><small class="badge bg-info"><?php echo htmlspecialchars($request['student_id']); ?></small>
                                                <small class="badge bg-secondary"><?php echo ucfirst($request['user_type']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo $request['processing_days']; ?> days processing</small>
                                        </td>
                                        <td>
                                            <span class="text-truncate" style="max-width: 150px; display: inline-block;" 
                                                  title="<?php echo htmlspecialchars($request['purpose']); ?>">
                                                <?php echo htmlspecialchars(substr($request['purpose'], 0, 40)) . (strlen($request['purpose']) > 40 ? '...' : ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($request['status']); ?> fs-6">
                                                <?php echo formatStatus($request['status']); ?>
                                            </span>
                                            <?php if ($request['preferred_release_date']): ?>
                                                <br><small class="text-muted">
                                                    Target: <?php echo date('M j', strtotime($request['preferred_release_date'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['request_date'])); ?>
                                            <br><small class="text-muted">
                                                <?php echo date('g:i A', strtotime($request['request_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-success dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-edit me-1"></i>Update
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'processing')">
                                                            <i class="fas fa-cog text-info me-2"></i>Processing
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'approved')">
                                                            <i class="fas fa-check text-success me-2"></i>Approved
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'denied')">
                                                            <i class="fas fa-times text-danger me-2"></i>Denied
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'ready_for_pickup')">
                                                            <i class="fas fa-hand-paper text-primary me-2"></i>Ready for Pickup
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $request['id']; ?>, 'completed')">
                                                            <i class="fas fa-check-circle text-dark me-2"></i>Completed
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No requests found</h5>
                        <p class="text-muted">No document requests match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    <input type="hidden" name="new_status" id="modal_new_status">
                    
                    <div class="mb-3">
                        <label class="form-label">New Status:</label>
                        <p id="modal_status_display" class="fw-bold"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Add any notes or comments about this status change..."></textarea>
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

<script>
function updateStatus(requestId, status) {
    document.getElementById('modal_request_id').value = requestId;
    document.getElementById('modal_new_status').value = status;
    document.getElementById('modal_status_display').textContent = status.replace('_', ' ').toUpperCase();
    
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function viewRequest(requestId) {
    window.open('view-request.php?id=' + requestId, '_blank');
}

function refreshPage() {
    location.reload();
}

function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('export-requests.php?' + params.toString(), '_blank');
}
</script>

<?php require_once 'includes/footer.php'; ?>