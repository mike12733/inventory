<?php
require_once 'config/database.php';
requireLogin();

$pdo = getDBConnection();
$success = '';
$error = '';

$equipment_id = $_GET['id'] ?? 0;
if (!$equipment_id) {
    header("Location: equipment.php");
    exit();
}

// Get equipment data
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    header("Location: equipment.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_code = $_POST['equipment_code'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $purchase_price = $_POST['purchase_price'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $warranty_expiry = $_POST['warranty_expiry'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    
    // Validate required fields
    if (empty($equipment_code) || empty($name)) {
        $error = 'Equipment code and name are required';
    } else {
        // Check if equipment code already exists (excluding current record)
        $stmt = $pdo->prepare("SELECT id FROM equipment WHERE equipment_code = ? AND id != ?");
        $stmt->execute([$equipment_code, $equipment_id]);
        if ($stmt->fetch()) {
            $error = 'Equipment code already exists';
        } else {
            // Check if barcode already exists (excluding current record)
            if (!empty($barcode)) {
                $stmt = $pdo->prepare("SELECT id FROM equipment WHERE barcode = ? AND id != ?");
                $stmt->execute([$barcode, $equipment_id]);
                if ($stmt->fetch()) {
                    $error = 'Barcode already exists';
                }
            }
            
            if (empty($error)) {
                // Update equipment
                $stmt = $pdo->prepare("UPDATE equipment SET equipment_code = ?, barcode = ?, name = ?, description = ?, category = ?, location = ?, status = ?, purchase_date = ?, purchase_price = ?, supplier = ?, warranty_expiry = ?, notes = ? WHERE id = ?");
                
                if ($stmt->execute([$equipment_code, $barcode, $name, $description, $category, $location, $status, $purchase_date, $purchase_price, $supplier, $warranty_expiry, $notes, $equipment_id])) {
                    logActivity('update', 'equipment', $equipment_id, "Equipment '$name' updated");
                    $success = 'Equipment updated successfully!';
                    
                    // Refresh equipment data
                    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
                    $stmt->execute([$equipment_id]);
                    $equipment = $stmt->fetch();
                } else {
                    $error = 'Failed to update equipment';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment - Inventory System</title>
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
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
                        <a class="nav-link" href="users.php">
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
                            <h4 class="mb-0">Edit Equipment</h4>
                            <div class="d-flex align-items-center">
                                <a href="equipment.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-arrow-left me-1"></i>Back to List
                                </a>
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
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>Edit Equipment: <?php echo htmlspecialchars($equipment['name']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="equipment_code" class="form-label">
                                                    <i class="fas fa-tag me-1"></i>Equipment Code *
                                                </label>
                                                <input type="text" class="form-control" id="equipment_code" name="equipment_code" 
                                                       value="<?php echo htmlspecialchars($equipment['equipment_code']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barcode" class="form-label">
                                                    <i class="fas fa-barcode me-1"></i>Barcode
                                                </label>
                                                <input type="text" class="form-control" id="barcode" name="barcode" 
                                                       value="<?php echo htmlspecialchars($equipment['barcode']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    <i class="fas fa-box me-1"></i>Equipment Name *
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($equipment['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="category" class="form-label">
                                                    <i class="fas fa-tags me-1"></i>Category
                                                </label>
                                                <select class="form-select" id="category" name="category">
                                                    <option value="">Select Category</option>
                                                    <option value="Computers" <?php echo $equipment['category'] === 'Computers' ? 'selected' : ''; ?>>Computers</option>
                                                    <option value="Printers" <?php echo $equipment['category'] === 'Printers' ? 'selected' : ''; ?>>Printers</option>
                                                    <option value="AV Equipment" <?php echo $equipment['category'] === 'AV Equipment' ? 'selected' : ''; ?>>AV Equipment</option>
                                                    <option value="Scanners" <?php echo $equipment['category'] === 'Scanners' ? 'selected' : ''; ?>>Scanners</option>
                                                    <option value="HVAC" <?php echo $equipment['category'] === 'HVAC' ? 'selected' : ''; ?>>HVAC</option>
                                                    <option value="Furniture" <?php echo $equipment['category'] === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                                                    <option value="Other" <?php echo $equipment['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-align-left me-1"></i>Description
                                        </label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($equipment['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="location" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Location
                                                </label>
                                                <input type="text" class="form-control" id="location" name="location" 
                                                       value="<?php echo htmlspecialchars($equipment['location']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">
                                                    <i class="fas fa-info-circle me-1"></i>Status
                                                </label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="available" <?php echo $equipment['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                    <option value="in_use" <?php echo $equipment['status'] === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                                    <option value="maintenance" <?php echo $equipment['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                    <option value="damaged" <?php echo $equipment['status'] === 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                                                    <option value="lost" <?php echo $equipment['status'] === 'lost' ? 'selected' : ''; ?>>Lost</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="purchase_date" class="form-label">
                                                    <i class="fas fa-calendar me-1"></i>Purchase Date
                                                </label>
                                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                                       value="<?php echo htmlspecialchars($equipment['purchase_date']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="purchase_price" class="form-label">
                                                    <i class="fas fa-money-bill me-1"></i>Purchase Price
                                                </label>
                                                <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                                                       value="<?php echo htmlspecialchars($equipment['purchase_price']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="supplier" class="form-label">
                                                    <i class="fas fa-building me-1"></i>Supplier
                                                </label>
                                                <input type="text" class="form-control" id="supplier" name="supplier" 
                                                       value="<?php echo htmlspecialchars($equipment['supplier']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="warranty_expiry" class="form-label">
                                                    <i class="fas fa-shield-alt me-1"></i>Warranty Expiry
                                                </label>
                                                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                                       value="<?php echo htmlspecialchars($equipment['warranty_expiry']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="notes" class="form-label">
                                                    <i class="fas fa-sticky-note me-1"></i>Notes
                                                </label>
                                                <input type="text" class="form-control" id="notes" name="notes" 
                                                       value="<?php echo htmlspecialchars($equipment['notes']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <a href="equipment.php" class="btn btn-secondary me-2">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Update Equipment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>