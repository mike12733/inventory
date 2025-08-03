<?php
$page_title = "Register";
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'user_type' => sanitizeInput($_POST['user_type'] ?? ''),
        'student_id' => sanitizeInput($_POST['student_id'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'graduation_year' => sanitizeInput($_POST['graduation_year'] ?? ''),
        'course' => sanitizeInput($_POST['course'] ?? '')
    ];
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($form_data['user_type']) || !in_array($form_data['user_type'], ['student', 'alumni'])) {
        $error = 'Please select a valid user type.';
    } elseif (empty($form_data['student_id'])) {
        $error = 'Student ID is required.';
    } elseif (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($form_data['first_name']) || empty($form_data['last_name'])) {
        $error = 'First name and last name are required.';
    } elseif (empty($password) || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif ($form_data['user_type'] === 'alumni' && empty($form_data['graduation_year'])) {
        $error = 'Graduation year is required for alumni.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if email or student ID already exists
            $sql = "SELECT id FROM users WHERE email = ? OR student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$form_data['email'], $form_data['student_id']]);
            
            if ($stmt->fetch()) {
                $error = 'Email or Student ID already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $sql = "INSERT INTO users (user_type, student_id, email, password, first_name, last_name, phone, address, graduation_year, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([
                    $form_data['user_type'],
                    $form_data['student_id'],
                    $form_data['email'],
                    $hashed_password,
                    $form_data['first_name'],
                    $form_data['last_name'],
                    $form_data['phone'],
                    $form_data['address'],
                    $form_data['graduation_year'] ?: null,
                    $form_data['course']
                ])) {
                    $user_id = $conn->lastInsertId();
                    
                    // Send welcome notification
                    sendNotification($user_id, 'Welcome to LNHS Portal', 'Your account has been created successfully. You can now request documents online.', 'success');
                    
                    $success = 'Registration successful! You can now login with your credentials.';
                    $form_data = []; // Clear form data
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Register for Portal
                    </h3>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            <div class="mt-2">
                                <a href="login.php" class="btn btn-success btn-sm">Login Now</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="user_type" class="form-label fw-bold">
                                    <i class="fas fa-user-tag me-2"></i>User Type
                                </label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="">Select Type</option>
                                    <option value="student" <?php echo ($form_data['user_type'] ?? '') === 'student' ? 'selected' : ''; ?>>
                                        Current Student
                                    </option>
                                    <option value="alumni" <?php echo ($form_data['user_type'] ?? '') === 'alumni' ? 'selected' : ''; ?>>
                                        Alumni/Graduate
                                    </option>
                                </select>
                                <div class="invalid-feedback">Please select your user type.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label fw-bold">
                                    <i class="fas fa-id-card me-2"></i>Student ID
                                </label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?php echo htmlspecialchars($form_data['student_id'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please enter your student ID.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label fw-bold">
                                    <i class="fas fa-user me-2"></i>First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please enter your first name.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-bold">
                                    <i class="fas fa-user me-2"></i>Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please enter your last name.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">
                                <i class="fas fa-phone me-2"></i>Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label fw-bold">
                                <i class="fas fa-map-marker-alt me-2"></i>Address
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="course" class="form-label fw-bold">
                                    <i class="fas fa-graduation-cap me-2"></i>Course/Program
                                </label>
                                <input type="text" class="form-control" id="course" name="course" 
                                       value="<?php echo htmlspecialchars($form_data['course'] ?? ''); ?>" 
                                       placeholder="e.g. Computer Science, Engineering">
                            </div>
                            
                            <div class="col-md-6 mb-3" id="graduation_year_field" style="display: none;">
                                <label for="graduation_year" class="form-label fw-bold">
                                    <i class="fas fa-calendar me-2"></i>Graduation Year
                                </label>
                                <select class="form-select" id="graduation_year" name="graduation_year">
                                    <option value="">Select Year</option>
                                    <?php for ($year = date('Y'); $year >= 1990; $year--): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($form_data['graduation_year'] ?? '') == $year ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-bold">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label fw-bold">
                                    <i class="fas fa-lock me-2"></i>Confirm Password
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Please confirm your password.</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide graduation year field based on user type
document.getElementById('user_type').addEventListener('change', function() {
    const graduationField = document.getElementById('graduation_year_field');
    const graduationInput = document.getElementById('graduation_year');
    
    if (this.value === 'alumni') {
        graduationField.style.display = 'block';
        graduationInput.required = true;
    } else {
        graduationField.style.display = 'none';
        graduationInput.required = false;
        graduationInput.value = '';
    }
});

// Initialize field visibility
document.addEventListener('DOMContentLoaded', function() {
    const userType = document.getElementById('user_type').value;
    if (userType === 'alumni') {
        document.getElementById('graduation_year_field').style.display = 'block';
        document.getElementById('graduation_year').required = true;
    }
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>