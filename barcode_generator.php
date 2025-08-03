<?php
require_once 'config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get equipment for barcode generation
$stmt = $pdo->query("SELECT id, equipment_code, name, barcode FROM equipment ORDER BY name");
$equipment_list = $stmt->fetchAll();

$selected_equipment = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $selected_equipment = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generator - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
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
        .barcode-container {
            text-align: center;
            padding: 20px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            margin: 20px 0;
        }
        .barcode-label {
            margin-top: 10px;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .barcode-container {
                border: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 no-print">
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
                        <a class="nav-link active" href="barcode_generator.php">
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
                    <nav class="navbar navbar-expand-lg no-print">
                        <div class="container-fluid">
                            <h4 class="mb-0">Barcode Generator</h4>
                            <div class="d-flex align-items-center">
                                <button onclick="window.print()" class="btn btn-success me-2">
                                    <i class="fas fa-print me-1"></i>Print Barcodes
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
                        <!-- Equipment Selection -->
                        <div class="card mb-4 no-print">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-search me-2"></i>Select Equipment for Barcode
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <select class="form-select" id="equipmentSelect" onchange="loadEquipment()">
                                            <option value="">Select Equipment</option>
                                            <?php foreach ($equipment_list as $equipment): ?>
                                                <option value="<?php echo $equipment['id']; ?>" 
                                                        data-barcode="<?php echo htmlspecialchars($equipment['barcode']); ?>"
                                                        data-name="<?php echo htmlspecialchars($equipment['name']); ?>"
                                                        data-code="<?php echo htmlspecialchars($equipment['equipment_code']); ?>">
                                                    <?php echo htmlspecialchars($equipment['equipment_code'] . ' - ' . $equipment['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-primary w-100" onclick="generateBarcode()">
                                            <i class="fas fa-barcode me-1"></i>Generate Barcode
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barcode Display -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-barcode me-2"></i>Generated Barcodes
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="barcodeContainer">
                                    <?php if ($selected_equipment): ?>
                                        <div class="barcode-container">
                                            <svg id="barcode-<?php echo $selected_equipment['id']; ?>"></svg>
                                            <div class="barcode-label">
                                                <?php echo htmlspecialchars($selected_equipment['equipment_code']); ?><br>
                                                <small><?php echo htmlspecialchars($selected_equipment['name']); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-barcode fa-3x mb-3"></i>
                                            <p>Select equipment to generate barcode</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Bulk Barcode Generation -->
                                <div class="mt-4 no-print">
                                    <h6><i class="fas fa-list me-2"></i>Generate All Barcodes</h6>
                                    <div class="row">
                                        <?php foreach (array_slice($equipment_list, 0, 12) as $equipment): ?>
                                            <div class="col-md-3 col-sm-4 col-6 mb-3">
                                                <div class="barcode-container">
                                                    <svg id="barcode-bulk-<?php echo $equipment['id']; ?>"></svg>
                                                    <div class="barcode-label">
                                                        <small><?php echo htmlspecialchars($equipment['equipment_code']); ?></small><br>
                                                        <small><?php echo htmlspecialchars(substr($equipment['name'], 0, 20)); ?>...</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate barcode for selected equipment
        function generateBarcode() {
            const select = document.getElementById('equipmentSelect');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                const barcode = selectedOption.getAttribute('data-barcode');
                const name = selectedOption.getAttribute('data-name');
                const code = selectedOption.getAttribute('data-code');
                const id = select.value;
                
                const container = document.getElementById('barcodeContainer');
                container.innerHTML = `
                    <div class="barcode-container">
                        <svg id="barcode-${id}"></svg>
                        <div class="barcode-label">
                            ${code}<br>
                            <small>${name}</small>
                        </div>
                    </div>
                `;
                
                // Generate the barcode
                JsBarcode(`#barcode-${id}`, barcode, {
                    format: "CODE128",
                    width: 2,
                    height: 100,
                    displayValue: true,
                    fontSize: 14,
                    margin: 10
                });
            }
        }
        
        // Load equipment data
        function loadEquipment() {
            const select = document.getElementById('equipmentSelect');
            if (select.value) {
                generateBarcode();
            }
        }
        
        // Generate all barcodes on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Generate bulk barcodes
            <?php foreach (array_slice($equipment_list, 0, 12) as $equipment): ?>
                JsBarcode('#barcode-bulk-<?php echo $equipment['id']; ?>', '<?php echo htmlspecialchars($equipment['barcode']); ?>', {
                    format: "CODE128",
                    width: 1.5,
                    height: 60,
                    displayValue: true,
                    fontSize: 10,
                    margin: 5
                });
            <?php endforeach; ?>
            
            // Generate selected equipment barcode if exists
            <?php if ($selected_equipment): ?>
                JsBarcode('#barcode-<?php echo $selected_equipment['id']; ?>', '<?php echo htmlspecialchars($selected_equipment['barcode']); ?>', {
                    format: "CODE128",
                    width: 2,
                    height: 100,
                    displayValue: true,
                    fontSize: 14,
                    margin: 10
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>