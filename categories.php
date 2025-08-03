<?php
require_once 'config/database.php';
requireLogin();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action == 'add') {
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            
            $categoryId = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['admin_id'], 'Added Category', 'categories', $categoryId, null, $_POST);
            
            $success = "Category added successfully!";
            
        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            
            // Get old values for logging
            $oldStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldValues = $oldStmt->fetch();
            
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'Updated Category', 'categories', $id, $oldValues, $_POST);
            
            $success = "Category updated successfully!";
            
        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if category has equipment
            $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM equipment WHERE category_id = ?");
            $countStmt->execute([$id]);
            $equipmentCount = $countStmt->fetch()['count'];
            
            if ($equipmentCount > 0) {
                $error = "Cannot delete category. It has $equipmentCount equipment items assigned to it.";
            } else {
                // Get category details for logging
                $categoryStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                $categoryStmt->execute([$id]);
                $category = $categoryStmt->fetch();
                
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                
                logActivity($pdo, $_SESSION['admin_id'], 'Deleted Category', 'categories', $id, $category, null);
                
                $success = "Category deleted successfully!";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get categories with equipment count
try {
    $categoriesStmt = $pdo->query("
        SELECT c.*, COUNT(e.id) as equipment_count 
        FROM categories c 
        LEFT JOIN equipment e ON c.id = e.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $categories = $categoriesStmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading categories: " . $e->getMessage();
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Categories Management');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-boxes"></i> Inventory System</h3>
                <small>Management Panel</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="equipment.php"><i class="fas fa-laptop"></i> Equipment</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="barcode_generator.php"><i class="fas fa-barcode"></i> Barcode Generator</a></li>
                <li><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1><i class="fas fa-tags"></i> Categories Management</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <button class="btn btn-success" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Categories List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Equipment Categories (<?php echo count($categories); ?> categories)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($categories) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Equipment Count</th>
                                            <th>Created Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($category['description']) ?: '-'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $category['equipment_count'] > 0 ? 'info' : 'secondary'; ?>">
                                                        <?php echo $category['equipment_count']; ?> items
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 5px;">
                                                        <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($category['equipment_count'] == 0): ?>
                                                            <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-secondary btn-sm" disabled title="Cannot delete category with equipment">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="equipment.php?category=<?php echo $category['id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View Equipment
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-tags" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No Categories Found</h3>
                                <p>Start organizing your equipment by creating categories.</p>
                                <button class="btn btn-success" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i> Create Your First Category
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-plus"></i> Add Category</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" method="POST" action="">
                    <input type="hidden" id="action" name="action" value="add">
                    <input type="hidden" id="category_id" name="id">
                    
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="Enter category name">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Enter category description (optional)"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Category
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Category';
            document.getElementById('action').value = 'add';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('name').focus();
        }
        
        function editCategory(id, name, description) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
            document.getElementById('action').value = 'edit';
            document.getElementById('category_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('description').value = description;
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('name').focus();
        }
        
        function deleteCategory(id, name) {
            if (confirm(`Are you sure you want to delete the category "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Auto-remove alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>