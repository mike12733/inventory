<?php
$page_title = "Login";
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $conn = getDBConnection();
            $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Create login notification
                sendNotification($user['id'], 'Login Successful', 'You have successfully logged into your account.', 'success');
                
                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header('Location: admin-dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100 align-items-center justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-4">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Portal
                    </h3>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Demo Credentials -->
                    <div class="bg-light p-3 rounded">
                        <h6 class="text-center mb-3">Demo Credentials</h6>
                        <div class="row text-center">
                            <div class="col-12 mb-2">
                                <strong>Admin:</strong><br>
                                <small>admin@lnhs.edu.ph / password</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>