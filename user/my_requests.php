<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] == 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get user's requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee, dt.processing_time
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    WHERE dr.user_id = ? 
    ORDER BY dr.request_date DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $limit, $offset]);
$requests = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_requests = $stmt->fetchColumn();
$total_pages = ceil($total_requests / $limit);

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
    <title>My Requests - <?php echo SITE_NAME; ?></title>
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
        .progress-track {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .progress-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            transform: translateX(-50%);
            z-index: 1;
        }
        .progress-step:last-child::before {
            display: none;
        }
        .progress-step.active::before {
            background: #667eea;
        }
        .progress-step.completed::before {
            background: #28a745;
        }
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            z-index: 2;
        }
        .step-icon.active {
            background: #667eea;
            color: white;
        }
        .step-icon.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="request_document.php">
                    <i class="fas fa-plus"></i> New Request
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
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> My Document Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No requests found</h5>
                                <p class="text-muted">You haven't made any document requests yet.</p>
                                <a href="request_document.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Make Your First Request
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
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
                                                    <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
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
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
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