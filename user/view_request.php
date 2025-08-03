<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] == 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$request_id) {
    header('Location: my_requests.php');
    exit();
}

$pdo = getDBConnection();

// Get request details
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee, dt.processing_time, u.full_name, u.email
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    WHERE dr.id = ? AND dr.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: my_requests.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Get status progress
function getStatusProgress($status) {
    $statuses = ['pending', 'processing', 'approved', 'ready_for_pickup', 'completed'];
    $current_index = array_search($status, $statuses);
    return $current_index !== false ? $current_index + 1 : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - <?php echo SITE_NAME; ?></title>
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
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #6c757d;
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
                <a class="nav-link" href="my_requests.php">
                    <i class="fas fa-list"></i> My Requests
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt"></i> Request Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Request Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="info-label">Request ID:</span>
                                    <span class="info-value">#<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Document Type:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($request['document_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Request Date:</span>
                                    <span class="info-value"><?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Preferred Release Date:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">
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
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Fee:</span>
                                    <span class="info-value">₱<?php echo number_format($request['fee'], 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Processing Time:</span>
                                    <span class="info-value"><?php echo $request['processing_time']; ?> days</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Last Updated:</span>
                                    <span class="info-value"><?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="mt-4">
                            <h6 class="info-label">Purpose of Request:</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($request['purpose']); ?></p>
                        </div>

                        <!-- Admin Notes -->
                        <?php if (!empty($request['admin_notes'])): ?>
                            <div class="mt-4">
                                <h6 class="info-label">Admin Notes:</h6>
                                <div class="alert alert-info">
                                    <?php echo htmlspecialchars($request['admin_notes']); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Uploaded File -->
                        <?php if (!empty($request['uploaded_file'])): ?>
                            <div class="mt-4">
                                <h6 class="info-label">Uploaded Requirements:</h6>
                                <a href="../uploads/<?php echo $request['uploaded_file']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download"></i> View File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Progress Tracking -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Progress Tracking</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress-track">
                            <div class="progress-step <?php echo getStatusProgress($request['status']) >= 1 ? 'completed' : (getStatusProgress($request['status']) == 1 ? 'active' : ''); ?>">
                                <div class="step-icon <?php echo getStatusProgress($request['status']) >= 1 ? 'completed' : (getStatusProgress($request['status']) == 1 ? 'active' : ''); ?>">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <small>Pending</small>
                            </div>
                            <div class="progress-step <?php echo getStatusProgress($request['status']) >= 2 ? 'completed' : (getStatusProgress($request['status']) == 2 ? 'active' : ''); ?>">
                                <div class="step-icon <?php echo getStatusProgress($request['status']) >= 2 ? 'completed' : (getStatusProgress($request['status']) == 2 ? 'active' : ''); ?>">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <small>Processing</small>
                            </div>
                            <div class="progress-step <?php echo getStatusProgress($request['status']) >= 3 ? 'completed' : (getStatusProgress($request['status']) == 3 ? 'active' : ''); ?>">
                                <div class="step-icon <?php echo getStatusProgress($request['status']) >= 3 ? 'completed' : (getStatusProgress($request['status']) == 3 ? 'active' : ''); ?>">
                                    <i class="fas fa-check"></i>
                                </div>
                                <small>Approved</small>
                            </div>
                            <div class="progress-step <?php echo getStatusProgress($request['status']) >= 4 ? 'completed' : (getStatusProgress($request['status']) == 4 ? 'active' : ''); ?>">
                                <div class="step-icon <?php echo getStatusProgress($request['status']) >= 4 ? 'completed' : (getStatusProgress($request['status']) == 4 ? 'active' : ''); ?>">
                                    <i class="fas fa-hand-holding"></i>
                                </div>
                                <small>Ready</small>
                            </div>
                        </div>

                        <!-- Status Messages -->
                        <div class="mt-3">
                            <?php
                            $status_messages = [
                                'pending' => 'Your request has been submitted and is waiting for review.',
                                'processing' => 'Your request is currently being processed by our staff.',
                                'approved' => 'Your request has been approved and is being prepared.',
                                'denied' => 'Your request has been denied. Please check admin notes for details.',
                                'ready_for_pickup' => 'Your document is ready for pickup! Please bring a valid ID.',
                                'completed' => 'Your request has been completed.'
                            ];
                            ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <?php echo $status_messages[$request['status']] ?? 'Status updated.'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="my_requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Requests
                            </a>
                            <?php if ($request['status'] == 'ready_for_pickup'): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="fas fa-check"></i> Ready for Pickup
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>