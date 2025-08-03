<?php
$page_title = "Profile";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if email already exists for another user
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->fetch()) {
                $error = 'Email address is already in use by another account.';
            } else {
                // Update user profile
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$first_name, $last_name, $email, $phone, $address, $user_id])) {
                    // Update session variables
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['email'] = $email;
                    
                    $success = 'Profile updated successfully!';
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}

// Get current user data
$conn = getDBConnection();
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-user-edit me-2"></i>My Profile
                </h2>
                <p class="text-muted mb-0">Update your personal information</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2"></i>Profile Information
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label fw-bold">
                                <i class="fas fa-user me-2"></i>First Name *
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            <div class="invalid-feedback">Please enter your first name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label fw-bold">
                                <i class="fas fa-user me-2"></i>Last Name *
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            <div class="invalid-feedback">Please enter your last name.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">
                            <i class="fas fa-envelope me-2"></i>Email Address *
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">
                            <i class="fas fa-phone me-2"></i>Phone Number
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label for="address" class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt me-2"></i>Address
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Read-only fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-id-card me-2"></i>Student ID
                            </label>
                            <input type="text" class="form-control-plaintext bg-light" 
                                   value="<?php echo htmlspecialchars($user['student_id']); ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-user-tag me-2"></i>Account Type
                            </label>
                            <input type="text" class="form-control-plaintext bg-light" 
                                   value="<?php echo ucfirst($user['user_type']); ?>" readonly>
                        </div>
                    </div>
                    
                    <?php if ($user['graduation_year']): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar me-2"></i>Graduation Year
                            </label>
                            <input type="text" class="form-control-plaintext bg-light" 
                                   value="<?php echo htmlspecialchars($user['graduation_year']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['course']): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-2"></i>Course/Program
                            </label>
                            <input type="text" class="form-control-plaintext bg-light" 
                                   value="<?php echo htmlspecialchars($user['course']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-clock me-2"></i>Member Since
                        </label>
                        <input type="text" class="form-control-plaintext bg-light" 
                               value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                        <a href="change-password.php" class="btn btn-outline-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Account Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Get user statistics
                $stats_sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                    SUM(total_fee) as total_spent
                    FROM document_requests WHERE user_id = ?";
                $stats_stmt = $conn->prepare($stats_sql);
                $stats_stmt->execute([$user_id]);
                $stats = $stats_stmt->fetch();
                ?>
                
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-primary mb-1"><?php echo $stats['total_requests']; ?></h4>
                            <small class="text-muted">Total Requests</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-warning mb-1"><?php echo $stats['pending_requests']; ?></h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-success mb-1"><?php echo $stats['completed_requests']; ?></h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-info mb-1">₱<?php echo number_format($stats['total_spent'], 2); ?></h4>
                            <small class="text-muted">Total Spent</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>