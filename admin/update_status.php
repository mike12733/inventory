<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$request_id) {
    header('Location: manage_requests.php');
    exit();
}

$pdo = getDBConnection();

// Get request details
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee, u.full_name, u.email, u.contact_number
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    WHERE dr.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: manage_requests.php');
    exit();
}

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);
    
    if (empty($new_status)) {
        $error = 'Please select a status.';
    } else {
        $stmt = $pdo->prepare("UPDATE document_requests SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        
        if ($stmt->execute([$new_status, $admin_notes, $request_id])) {
            // Create notification for user
            $notification_title = 'Request Status Updated';
            $notification_message = "Your document request #" . str_pad($request_id, 4, '0', STR_PAD_LEFT) . " status has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
            
            if (!empty($admin_notes)) {
                $notification_message .= "\n\nAdmin Notes: " . $admin_notes;
            }
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$request['user_id'], $notification_title, $notification_message, 'portal']);
            
            // Log admin action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'update_status', "Updated request #$request_id status to $new_status", $_SERVER['REMOTE_ADDR']]);
            
            $success = 'Request status updated successfully! User has been notified.';
        } else {
            $error = 'Failed to update request status.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Request Status - <?php echo SITE_NAME; ?></title>
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
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?> - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="manage_requests.php">
                    <i class="fas fa-list"></i> Manage Requests
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-edit"></i> Update Request Status</h2>
                    <a href="manage_requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Requests
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Request Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Request Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="info-label">Request ID:</span>
                                    <span class="info-value">#<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">User:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($request['full_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($request['email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Contact:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($request['contact_number'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Document:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($request['document_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Request Date:</span>
                                    <span class="info-value"><?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Preferred Date:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Fee:</span>
                                    <span class="info-value">₱<?php echo number_format($request['fee'], 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Current Status:</span>
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
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-edit"></i> Update Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">New Status *</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $request['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="denied" <?php echo $request['status'] == 'denied' ? 'selected' : ''; ?>>Denied</option>
                                            <option value="ready_for_pickup" <?php echo $request['status'] == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                            <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_notes" class="form-label">Admin Notes</label>
                                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" 
                                            placeholder="Add any notes or instructions for the user..."><?php echo htmlspecialchars($request['admin_notes']); ?></textarea>
                                        <small class="text-muted">This will be visible to the user and included in the notification.</small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Status
                                        </button>
                                        <a href="manage_requests.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purpose and Current Notes -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Purpose of Request</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted"><?php echo htmlspecialchars($request['purpose']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Current Admin Notes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($request['admin_notes'])): ?>
                                    <p class="text-muted"><?php echo htmlspecialchars($request['admin_notes']); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">No admin notes yet.</p>
                                <?php endif; ?>
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