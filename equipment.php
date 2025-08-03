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
            $category_id = (int)$_POST['category_id'];
            $serial_number = sanitizeInput($_POST['serial_number']);
            $model = sanitizeInput($_POST['model']);
            $brand = sanitizeInput($_POST['brand']);
            $location = sanitizeInput($_POST['location']);
            $purchase_date = $_POST['purchase_date'] ?: null;
            $purchase_price = $_POST['purchase_price'] ?: null;
            $warranty_expiry = $_POST['warranty_expiry'] ?: null;
            
            // Generate unique barcode
            $barcode = generateBarcode();
            
            $stmt = $pdo->prepare("INSERT INTO equipment (name, description, category_id, barcode, serial_number, model, brand, location, purchase_date, purchase_price, warranty_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $category_id, $barcode, $serial_number, $model, $brand, $location, $purchase_date, $purchase_price, $warranty_expiry]);
            
            $equipmentId = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['admin_id'], 'Added Equipment', 'equipment', $equipmentId, null, $_POST);
            
            $success = "Equipment added successfully with barcode: $barcode";
            
        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            
            // Get old values for logging
            $oldStmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldValues = $oldStmt->fetch();
            
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $category_id = (int)$_POST['category_id'];
            $serial_number = sanitizeInput($_POST['serial_number']);
            $model = sanitizeInput($_POST['model']);
            $brand = sanitizeInput($_POST['brand']);
            $status = $_POST['status'];
            $location = sanitizeInput($_POST['location']);
            $purchase_date = $_POST['purchase_date'] ?: null;
            $purchase_price = $_POST['purchase_price'] ?: null;
            $warranty_expiry = $_POST['warranty_expiry'] ?: null;
            
            $stmt = $pdo->prepare("UPDATE equipment SET name = ?, description = ?, category_id = ?, serial_number = ?, model = ?, brand = ?, status = ?, location = ?, purchase_date = ?, purchase_price = ?, warranty_expiry = ? WHERE id = ?");
            $stmt->execute([$name, $description, $category_id, $serial_number, $model, $brand, $status, $location, $purchase_date, $purchase_price, $warranty_expiry, $id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'Updated Equipment', 'equipment', $id, $oldValues, $_POST);
            
            $success = "Equipment updated successfully!";
            
        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            
            // Get equipment details for logging
            $equipmentStmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
            $equipmentStmt->execute([$id]);
            $equipment = $equipmentStmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'Deleted Equipment', 'equipment', $id, $equipment, null);
            
            $success = "Equipment deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get equipment list with filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(e.name LIKE ? OR e.barcode LIKE ? OR e.serial_number LIKE ? OR e.model LIKE ? OR e.brand LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($category_filter) {
    $whereConditions[] = "e.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    if (strpos($status_filter, ',') !== false) {
        $statuses = explode(',', $status_filter);
        $placeholders = str_repeat('?,', count($statuses) - 1) . '?';
        $whereConditions[] = "e.status IN ($placeholders)";
        $params = array_merge($params, $statuses);
    } else {
        $whereConditions[] = "e.status = ?";
        $params[] = $status_filter;
    }
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$equipmentStmt = $pdo->prepare("
    SELECT e.*, c.name as category_name 
    FROM equipment e 
    LEFT JOIN categories c ON e.category_id = c.id 
    $whereClause 
    ORDER BY e.created_at DESC
");
$equipmentStmt->execute($params);
$equipment = $equipmentStmt->fetchAll();

// Get categories for dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

// Get specific equipment for edit
$editEquipment = null;
if (isset($_GET['edit'])) {
    $editStmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $editStmt->execute([$_GET['edit']]);
    $editEquipment = $editStmt->fetch();
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Equipment Management');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - Inventory System</title>
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
                <li><a href="equipment.php" class="active"><i class="fas fa-laptop"></i> Equipment</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
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
                    <h1><i class="fas fa-laptop"></i> Equipment Management</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <button class="btn btn-success" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Equipment
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
                
                <!-- Search and Filter -->
                <div class="search-filter">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Search Equipment</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, barcode, serial number..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="form-control">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Available" <?php echo $status_filter == 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="In Use" <?php echo $status_filter == 'In Use' ? 'selected' : ''; ?>>In Use</option>
                                    <option value="Maintenance" <?php echo $status_filter == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Damaged" <?php echo $status_filter == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="Lost" <?php echo $status_filter == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="equipment.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Equipment List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Equipment List (<?php echo count($equipment); ?> items)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($equipment) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Barcode</th>
                                            <th>Category</th>
                                            <th>Brand/Model</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($equipment as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <?php if ($item['serial_number']): ?>
                                                        <br><small>SN: <?php echo htmlspecialchars($item['serial_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?php echo $item['barcode']; ?></code>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($item['brand']); ?>
                                                    <?php if ($item['model']): ?>
                                                        <br><small><?php echo htmlspecialchars($item['model']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = 'secondary';
                                                    switch ($item['status']) {
                                                        case 'Available': $badgeClass = 'success'; break;
                                                        case 'In Use': $badgeClass = 'warning'; break;
                                                        case 'Maintenance': $badgeClass = 'info'; break;
                                                        case 'Damaged': $badgeClass = 'danger'; break;
                                                        case 'Lost': $badgeClass = 'danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $badgeClass; ?>">
                                                        <?php echo $item['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['location']); ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 5px;">
                                                        <button class="btn btn-info btn-sm" onclick="viewEquipment(<?php echo $item['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-warning btn-sm" onclick="editEquipment(<?php echo $item['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" onclick="deleteEquipment(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <a href="barcode_generator.php?equipment_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-barcode"></i>
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
                                <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No Equipment Found</h3>
                                <p>No equipment matches your search criteria.</p>
                                <button class="btn btn-success" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i> Add Your First Equipment
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Equipment Modal -->
    <div id="equipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-plus"></i> Add Equipment</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="equipmentForm" method="POST" action="">
                    <input type="hidden" id="action" name="action" value="add">
                    <input type="hidden" id="equipment_id" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Equipment Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="model">Model</label>
                            <input type="text" id="model" name="model" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="serial_number">Serial Number</label>
                            <input type="text" id="serial_number" name="serial_number" class="form-control">
                        </div>
                        <div class="form-group" id="statusGroup" style="display: none;">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Available">Available</option>
                                <option value="In Use">In Use</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase_date">Purchase Date</label>
                            <input type="date" id="purchase_date" name="purchase_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="purchase_price">Purchase Price</label>
                            <input type="number" id="purchase_price" name="purchase_price" class="form-control" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="warranty_expiry">Warranty Expiry</label>
                        <input type="date" id="warranty_expiry" name="warranty_expiry" class="form-control">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Equipment
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Equipment Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Equipment Details</h3>
                <button class="close" onclick="document.getElementById('viewModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body" id="equipmentDetails">
                <!-- Equipment details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Equipment';
            document.getElementById('action').value = 'add';
            document.getElementById('equipmentForm').reset();
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('equipmentModal').style.display = 'block';
        }
        
        function editEquipment(id) {
            // Fetch equipment data and populate form
            fetch(`get_equipment.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const equipment = data.equipment;
                        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Equipment';
                        document.getElementById('action').value = 'edit';
                        document.getElementById('equipment_id').value = equipment.id;
                        document.getElementById('name').value = equipment.name;
                        document.getElementById('description').value = equipment.description;
                        document.getElementById('category_id').value = equipment.category_id;
                        document.getElementById('brand').value = equipment.brand;
                        document.getElementById('model').value = equipment.model;
                        document.getElementById('serial_number').value = equipment.serial_number;
                        document.getElementById('status').value = equipment.status;
                        document.getElementById('location').value = equipment.location;
                        document.getElementById('purchase_date').value = equipment.purchase_date;
                        document.getElementById('purchase_price').value = equipment.purchase_price;
                        document.getElementById('warranty_expiry').value = equipment.warranty_expiry;
                        document.getElementById('statusGroup').style.display = 'block';
                        document.getElementById('equipmentModal').style.display = 'block';
                    }
                });
        }
        
        function viewEquipment(id) {
            fetch(`get_equipment.php?id=${id}&view=1`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('equipmentDetails').innerHTML = html;
                    document.getElementById('viewModal').style.display = 'block';
                });
        }
        
        function deleteEquipment(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
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
            document.getElementById('equipmentModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('equipmentModal');
            const viewModal = document.getElementById('viewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
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