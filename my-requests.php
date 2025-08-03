<?php
$page_title = "My Requests";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

// Only students and alumni can view their requests
if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit();
}

$user_id = getCurrentUserId();

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$where_conditions = ["dr.user_id = ?"];
$params = [$user_id];

if (!empty($status_filter)) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(dt.document_name LIKE ? OR dr.purpose LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$conn = getDBConnection();
$count_sql = "SELECT COUNT(*) as total 
              FROM document_requests dr 
              JOIN document_types dt ON dr.document_type_id = dt.id 
              WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_requests = $count_stmt->fetch()['total'];

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_requests / $per_page);

// Get requests
$sql = "SELECT dr.*, dt.document_name, dt.fee, dt.processing_days,
               u_processed.first_name as processed_by_name
        FROM document_requests dr 
        JOIN document_types dt ON dr.document_type_id = dt.id 
        LEFT JOIN users u_processed ON dr.processed_by = u_processed.id
        WHERE $where_clause 
        ORDER BY dr.request_date DESC 
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
                <i class="fas fa-list me-3"></i>My Document Requests
            </h1>
            <p class="lead mb-0">Track and manage your submitted requests</p>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by document type or purpose">
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="my-requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                <?php echo $total_requests; ?> Request(s) Found
            </h5>
            <a href="request-document.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>New Request
            </a>
        </div>
    </div>
</div>

<!-- Requests List -->
<div class="row">
    <div class="col-12">
        <?php if ($requests): ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="requestsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Request ID</th>
                                    <th>Document</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Fee</th>
                                    <th>Date Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                                                <br><small class="text-muted">
                                                    Processing: <?php echo $request['processing_days']; ?> days
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                                                  title="<?php echo htmlspecialchars($request['purpose']); ?>">
                                                <?php echo htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($request['status']); ?> fs-6">
                                                <i class="fas fa-<?php echo $request['status'] === 'pending' ? 'clock' : ($request['status'] === 'processing' ? 'cog' : ($request['status'] === 'approved' ? 'check' : ($request['status'] === 'denied' ? 'times' : ($request['status'] === 'ready_for_pickup' ? 'hand-paper' : 'check-circle')))); ?> me-1"></i>
                                                <?php echo formatStatus($request['status']); ?>
                                            </span>
                                            <?php if ($request['preferred_release_date']): ?>
                                                <br><small class="text-muted">
                                                    Target: <?php echo date('M j, Y', strtotime($request['preferred_release_date'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>₱<?php echo number_format($request['total_fee'], 2); ?></strong>
                                            <br><small class="badge bg-<?php echo $request['payment_status'] === 'paid' ? 'success' : ($request['payment_status'] === 'waived' ? 'info' : 'warning'); ?>">
                                                <?php echo ucfirst($request['payment_status']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['request_date'])); ?>
                                            <br><small class="text-muted">
                                                <?php echo date('g:i A', strtotime($request['request_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">No requests found</h4>
                    <p class="text-muted mb-4">
                        <?php echo !empty($status_filter) || !empty($search) ? 'Try adjusting your filters.' : 'You haven\'t submitted any document requests yet.'; ?>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="request-document.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Request
                        </a>
                        <?php if (!empty($status_filter) || !empty($search)): ?>
                            <a href="my-requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Legend -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Status Legend
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-warning fs-6">
                            <i class="fas fa-clock me-1"></i>Pending
                        </span>
                        <br><small class="text-muted">Waiting for review</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-info fs-6">
                            <i class="fas fa-cog me-1"></i>Processing
                        </span>
                        <br><small class="text-muted">Being prepared</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check me-1"></i>Approved
                        </span>
                        <br><small class="text-muted">Request approved</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-danger fs-6">
                            <i class="fas fa-times me-1"></i>Denied
                        </span>
                        <br><small class="text-muted">Request rejected</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-primary fs-6">
                            <i class="fas fa-hand-paper me-1"></i>Ready
                        </span>
                        <br><small class="text-muted">Ready for pickup</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <span class="badge bg-dark fs-6">
                            <i class="fas fa-check-circle me-1"></i>Completed
                        </span>
                        <br><small class="text-muted">Request completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelRequest(requestId) {
    if (confirm('Are you sure you want to cancel this request? This action cannot be undone.')) {
        // Create form to submit cancellation
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cancel-request.php';
        
        const requestIdInput = document.createElement('input');
        requestIdInput.type = 'hidden';
        requestIdInput.name = 'request_id';
        requestIdInput.value = requestId;
        
        form.appendChild(requestIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    searchTable('search', 'requestsTable');
});
</script>

<?php require_once 'includes/footer.php'; ?>