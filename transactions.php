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
            $equipment_id = (int)$_POST['equipment_id'];
            $transaction_type = $_POST['transaction_type'];
            $quantity = (int)$_POST['quantity'];
            $notes = sanitizeInput($_POST['notes']);
            $user_name = sanitizeInput($_POST['user_name']);
            $location_from = sanitizeInput($_POST['location_from']);
            $location_to = sanitizeInput($_POST['location_to']);
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Add transaction record
            $stmt = $pdo->prepare("INSERT INTO inventory_transactions (equipment_id, transaction_type, quantity, notes, user_name, location_from, location_to, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$equipment_id, $transaction_type, $quantity, $notes, $user_name, $location_from, $location_to, $_SESSION['admin_id']]);
            
            $transactionId = $pdo->lastInsertId();
            
            // Update equipment status and location based on transaction type
            $updateFields = [];
            $updateParams = [];
            
            switch ($transaction_type) {
                case 'Check Out':
                    $updateFields[] = "status = 'In Use'";
                    if ($location_to) {
                        $updateFields[] = "location = ?";
                        $updateParams[] = $location_to;
                    }
                    break;
                case 'Check In':
                case 'Return':
                    $updateFields[] = "status = 'Available'";
                    if ($location_to) {
                        $updateFields[] = "location = ?";
                        $updateParams[] = $location_to;
                    }
                    break;
                case 'Maintenance':
                    $updateFields[] = "status = 'Maintenance'";
                    if ($location_to) {
                        $updateFields[] = "location = ?";
                        $updateParams[] = $location_to;
                    }
                    break;
                case 'Transfer':
                    if ($location_to) {
                        $updateFields[] = "location = ?";
                        $updateParams[] = $location_to;
                    }
                    break;
            }
            
            if (!empty($updateFields)) {
                $updateParams[] = $equipment_id;
                $updateStmt = $pdo->prepare("UPDATE equipment SET " . implode(', ', $updateFields) . " WHERE id = ?");
                $updateStmt->execute($updateParams);
            }
            
            $pdo->commit();
            
            logActivity($pdo, $_SESSION['admin_id'], 'Added Transaction', 'inventory_transactions', $transactionId, null, $_POST);
            
            $success = "Transaction recorded successfully!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get transactions with filters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$equipment_filter = $_GET['equipment'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(e.name LIKE ? OR it.notes LIKE ? OR it.user_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($type_filter) {
    $whereConditions[] = "it.transaction_type = ?";
    $params[] = $type_filter;
}

if ($equipment_filter) {
    $whereConditions[] = "it.equipment_id = ?";
    $params[] = $equipment_filter;
}

if ($date_from) {
    $whereConditions[] = "DATE(it.transaction_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = "DATE(it.transaction_date) <= ?";
    $params[] = $date_to;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Get transactions
    $transactionsStmt = $pdo->prepare("
        SELECT it.*, e.name as equipment_name, e.barcode, a.full_name as admin_name
        FROM inventory_transactions it
        JOIN equipment e ON it.equipment_id = e.id
        JOIN admins a ON it.admin_id = a.id
        $whereClause
        ORDER BY it.transaction_date DESC
    ");
    $transactionsStmt->execute($params);
    $transactions = $transactionsStmt->fetchAll();
    
    // Get equipment list for dropdown
    $equipmentStmt = $pdo->query("SELECT id, name, barcode FROM equipment ORDER BY name");
    $equipmentList = $equipmentStmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading transactions: " . $e->getMessage();
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Transactions');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Inventory System</title>
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
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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
                    <h1><i class="fas fa-exchange-alt"></i> Inventory Transactions</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <button class="btn btn-success" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> New Transaction
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
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search equipment, notes, or user..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label for="type">Transaction Type</label>
                                <select id="type" name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="Check In" <?php echo $type_filter == 'Check In' ? 'selected' : ''; ?>>Check In</option>
                                    <option value="Check Out" <?php echo $type_filter == 'Check Out' ? 'selected' : ''; ?>>Check Out</option>
                                    <option value="Maintenance" <?php echo $type_filter == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Return" <?php echo $type_filter == 'Return' ? 'selected' : ''; ?>>Return</option>
                                    <option value="Transfer" <?php echo $type_filter == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="equipment">Equipment</label>
                                <select id="equipment" name="equipment" class="form-control">
                                    <option value="">All Equipment</option>
                                    <?php foreach ($equipmentList as $eq): ?>
                                        <option value="<?php echo $eq['id']; ?>" <?php echo $equipment_filter == $eq['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($eq['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_from">Date From</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">Date To</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="transactions.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Transactions List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Transaction History (<?php echo count($transactions); ?> records)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Equipment</th>
                                            <th>Type</th>
                                            <th>User</th>
                                            <th>Location</th>
                                            <th>Admin</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></small><br>
                                                    <small style="color: #666;"><?php echo date('H:i:s', strtotime($transaction['transaction_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($transaction['equipment_name']); ?></strong><br>
                                                    <small><code><?php echo $transaction['barcode']; ?></code></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $typeClass = 'secondary';
                                                    switch ($transaction['transaction_type']) {
                                                        case 'Check In':
                                                        case 'Return':
                                                            $typeClass = 'success';
                                                            break;
                                                        case 'Check Out':
                                                            $typeClass = 'warning';
                                                            break;
                                                        case 'Maintenance':
                                                            $typeClass = 'info';
                                                            break;
                                                        case 'Transfer':
                                                            $typeClass = 'primary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $typeClass; ?>">
                                                        <?php echo $transaction['transaction_type']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($transaction['user_name']) ?: '-'; ?></td>
                                                <td>
                                                    <?php if ($transaction['location_from'] && $transaction['location_to']): ?>
                                                        <small><?php echo htmlspecialchars($transaction['location_from']); ?></small><br>
                                                        <i class="fas fa-arrow-down" style="color: #666;"></i><br>
                                                        <small><?php echo htmlspecialchars($transaction['location_to']); ?></small>
                                                    <?php elseif ($transaction['location_to']): ?>
                                                        <small><?php echo htmlspecialchars($transaction['location_to']); ?></small>
                                                    <?php elseif ($transaction['location_from']): ?>
                                                        <small><?php echo htmlspecialchars($transaction['location_from']); ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($transaction['admin_name']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($transaction['notes']): ?>
                                                        <small><?php echo htmlspecialchars(substr($transaction['notes'], 0, 50)); ?><?php echo strlen($transaction['notes']) > 50 ? '...' : ''; ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-exchange-alt" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No Transactions Found</h3>
                                <p>No transactions match your search criteria.</p>
                                <button class="btn btn-success" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i> Record Your First Transaction
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Transaction Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> New Transaction</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="transactionForm" method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="equipment_id">Equipment *</label>
                            <select id="equipment_id" name="equipment_id" class="form-control" required>
                                <option value="">Select Equipment</option>
                                <?php foreach ($equipmentList as $eq): ?>
                                    <option value="<?php echo $eq['id']; ?>">
                                        <?php echo htmlspecialchars($eq['name']); ?> (<?php echo $eq['barcode']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction_type">Transaction Type *</label>
                            <select id="transaction_type" name="transaction_type" class="form-control" required onchange="updateFormFields()">
                                <option value="">Select Type</option>
                                <option value="Check Out">Check Out</option>
                                <option value="Check In">Check In</option>
                                <option value="Return">Return</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label for="user_name">User/Person</label>
                            <input type="text" id="user_name" name="user_name" class="form-control" placeholder="Enter person's name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location_from">From Location</label>
                            <input type="text" id="location_from" name="location_from" class="form-control" placeholder="Current location">
                        </div>
                        <div class="form-group">
                            <label for="location_to">To Location</label>
                            <input type="text" id="location_to" name="location_to" class="form-control" placeholder="Destination location">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Additional notes or comments"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Record Transaction
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
            document.getElementById('transactionForm').reset();
            document.getElementById('transactionModal').style.display = 'block';
            document.getElementById('equipment_id').focus();
        }
        
        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }
        
        function updateFormFields() {
            const type = document.getElementById('transaction_type').value;
            const userField = document.getElementById('user_name');
            const fromField = document.getElementById('location_from');
            const toField = document.getElementById('location_to');
            
            // Reset required attributes
            userField.required = false;
            fromField.required = false;
            toField.required = false;
            
            // Update field requirements based on transaction type
            switch (type) {
                case 'Check Out':
                    userField.required = true;
                    toField.required = true;
                    userField.placeholder = 'Person checking out equipment';
                    toField.placeholder = 'Where is the equipment going?';
                    break;
                case 'Check In':
                case 'Return':
                    toField.required = true;
                    userField.placeholder = 'Person returning equipment';
                    toField.placeholder = 'Where is the equipment being stored?';
                    break;
                case 'Maintenance':
                    toField.required = true;
                    userField.placeholder = 'Technician or service person';
                    toField.placeholder = 'Maintenance location';
                    break;
                case 'Transfer':
                    fromField.required = true;
                    toField.required = true;
                    userField.placeholder = 'Person handling transfer';
                    fromField.placeholder = 'Current location';
                    toField.placeholder = 'New location';
                    break;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('transactionModal');
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
        
        // Set default date range if no filters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('date_from') && !urlParams.has('date_to') && !urlParams.has('search') && !urlParams.has('type')) {
                const today = new Date();
                const sevenDaysAgo = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
                
                document.getElementById('date_from').value = sevenDaysAgo.toISOString().split('T')[0];
                document.getElementById('date_to').value = today.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>