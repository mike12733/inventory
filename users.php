<?php
require_once 'config/database.php';
requireLogin();

$pdo = getDBConnection();
$success = '';
$error = '';

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $role = $_POST['role'] ?? 'user';
                
                if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                    $error = 'All fields are required';
                } else {
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $error = 'Username or email already exists';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$username, $email, $hashed_password, $full_name, $role])) {
                            $user_id = $pdo->lastInsertId();
                            logActivity('create', 'users', $user_id, "User '$username' added");
                            $success = 'User added successfully!';
                        } else {
                            $error = 'Failed to add user';
                        }
                    }
                }
                break;
                
            case 'delete':
                $user_id = $_POST['user_id'] ?? 0;
                if ($user_id == $_SESSION['user_id']) {
                    $error = 'Cannot delete your own account';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        logActivity('delete', 'users', $user_id, 'User deleted');
                        $success = 'User deleted successfully!';
                    } else {
                        $error = 'Failed to delete user';
                    }
                }
                break;
        }
    }
}

// Get users list
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-boxes fa-2x mb-2"></i>
                        <h5>Inventory System</h5>
                        <small>Equipment Management</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="equipment.php">
                            <i class="fas fa-boxes me-2"></i>Equipment
                        </a>
                        <a class="nav-link" href="add_equipment.php">
                            <i class="fas fa-plus me-2"></i>Add Equipment
                        </a>
                        <a class="nav-link" href="barcode_generator.php">
                            <i class="fas fa-barcode me-2"></i>Barcode Generator
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="activity_logs.php">
                            <i class="fas fa-history me-2"></i>Activity Logs
                        </a>
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Top Navbar -->
                    <nav class="navbar navbar-expand-lg">
                        <div class="container-fluid">
                            <h4 class="mb-0">User Management</h4>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-plus me-1"></i>Add User
                                </button>
                                <span class="me-3">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                </span>
                                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a>
                            </div>
                        </div>
                    </nav>
                    
                    <div class="p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Users List -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>System Users
                                    <span class="badge bg-secondary ms-2"><?php echo count($users_list); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users_list as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-info ms-1">Current</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="userName"></span>"?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="userId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            document.getElementById('userId').value = id;
            document.getElementById('userName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>
</html>