<?php
require_once 'config/database.php';
requireLogin();

$error = '';
$success = '';
$equipment = null;
$barcode = '';

// Get equipment if ID is provided
if (isset($_GET['equipment_id'])) {
    $equipment_id = (int)$_GET['equipment_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
        $stmt->execute([$equipment_id]);
        $equipment = $stmt->fetch();
        if ($equipment) {
            $barcode = $equipment['barcode'];
        }
    } catch (Exception $e) {
        $error = "Error loading equipment: " . $e->getMessage();
    }
}

// Handle manual barcode input
if (isset($_GET['barcode'])) {
    $barcode = sanitizeInput($_GET['barcode']);
    // Try to find equipment with this barcode
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE barcode = ?");
        $stmt->execute([$barcode]);
        $equipment = $stmt->fetch();
    } catch (Exception $e) {
        // Barcode might not exist in database, that's okay
    }
}

// Generate new barcode if requested
if (isset($_POST['generate_new'])) {
    $barcode = generateBarcode();
    $success = "New barcode generated: $barcode";
}

// Function to generate barcode as SVG (Code 128 style bars)
function generateBarcodeSVG($text, $width = 300, $height = 100) {
    // Simple barcode pattern generation (basic representation)
    $patterns = [
        '0' => '101', '1' => '110', '2' => '011', '3' => '100', '4' => '001',
        '5' => '111', '6' => '000', '7' => '101', '8' => '010', '9' => '101',
        'A' => '110', 'B' => '011', 'C' => '101', 'D' => '110', 'E' => '011',
        'F' => '101', 'G' => '110', 'H' => '011', 'I' => '101', 'J' => '110',
        'K' => '011', 'L' => '101', 'M' => '110', 'N' => '011', 'O' => '101',
        'P' => '110', 'Q' => '011', 'R' => '101', 'S' => '110', 'T' => '011',
        'U' => '101', 'V' => '110', 'W' => '011', 'X' => '101', 'Y' => '110',
        'Z' => '011'
    ];
    
    $barPattern = '110'; // Start pattern
    
    // Convert text to pattern
    for ($i = 0; $i < strlen($text); $i++) {
        $char = strtoupper($text[$i]);
        if (isset($patterns[$char])) {
            $barPattern .= $patterns[$char];
        } else {
            $barPattern .= '101'; // Default pattern
        }
    }
    
    $barPattern .= '011'; // End pattern
    
    $barWidth = $width / strlen($barPattern);
    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="white"/>';
    
    $x = 0;
    for ($i = 0; $i < strlen($barPattern); $i++) {
        if ($barPattern[$i] == '1') {
            $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="' . ($height - 30) . '" fill="black"/>';
        }
        $x += $barWidth;
    }
    
    // Add text below barcode
    $svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="monospace" font-size="12" fill="black">' . htmlspecialchars($text) . '</text>';
    $svg .= '</svg>';
    
    return $svg;
}

logActivity($pdo, $_SESSION['admin_id'], 'Accessed Barcode Generator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generator - Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .barcode-display {
            background: white;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }
        
        .barcode-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        @media print {
            body * { visibility: hidden; }
            .barcode-display, .barcode-display * { visibility: visible; }
            .barcode-display { position: absolute; top: 0; left: 0; width: 100%; }
            .barcode-actions { display: none; }
        }
    </style>
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
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="barcode_generator.php" class="active"><i class="fas fa-barcode"></i> Barcode Generator</a></li>
                <li><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1><i class="fas fa-barcode"></i> Barcode Generator</h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <a href="equipment.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Equipment
                        </a>
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
                
                <!-- Barcode Input -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Generate Barcode</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="barcode">Enter Barcode or Equipment ID</label>
                                    <input type="text" 
                                           id="barcode" 
                                           name="barcode" 
                                           class="form-control" 
                                           placeholder="Enter barcode text..."
                                           value="<?php echo htmlspecialchars($barcode); ?>"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-barcode"></i> Generate
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <form method="POST" action="" style="display: inline;">
                                <button type="submit" name="generate_new" class="btn btn-success">
                                    <i class="fas fa-magic"></i> Generate New Unique Barcode
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <?php if ($barcode): ?>
                    <!-- Equipment Information -->
                    <?php if ($equipment): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-info-circle"></i> Equipment Information</h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                    <div>
                                        <strong>Name:</strong><br>
                                        <?php echo htmlspecialchars($equipment['name']); ?>
                                    </div>
                                    <div>
                                        <strong>Serial Number:</strong><br>
                                        <?php echo htmlspecialchars($equipment['serial_number']) ?: 'N/A'; ?>
                                    </div>
                                    <div>
                                        <strong>Status:</strong><br>
                                        <span class="badge badge-<?php 
                                            echo match($equipment['status']) {
                                                'Available' => 'success',
                                                'In Use' => 'warning',
                                                'Maintenance' => 'info',
                                                'Damaged', 'Lost' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo $equipment['status']; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <strong>Location:</strong><br>
                                        <?php echo htmlspecialchars($equipment['location']) ?: 'N/A'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Barcode Display -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-barcode"></i> Generated Barcode</h3>
                        </div>
                        <div class="card-body">
                            <div class="barcode-display" id="barcodeDisplay">
                                <?php echo generateBarcodeSVG($barcode, 400, 120); ?>
                                
                                <div class="barcode-actions">
                                    <button class="btn btn-primary" onclick="printBarcode()">
                                        <i class="fas fa-print"></i> Print Barcode
                                    </button>
                                    <button class="btn btn-success" onclick="downloadBarcode()">
                                        <i class="fas fa-download"></i> Download SVG
                                    </button>
                                    <button class="btn btn-info" onclick="copyBarcode()">
                                        <i class="fas fa-copy"></i> Copy Text
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barcode Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info"></i> Barcode Information</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <h4>Barcode Details</h4>
                                    <table class="table">
                                        <tr>
                                            <td><strong>Barcode Text:</strong></td>
                                            <td><code><?php echo htmlspecialchars($barcode); ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Length:</strong></td>
                                            <td><?php echo strlen($barcode); ?> characters</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td>Code 128 (Simplified)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Generated:</strong></td>
                                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div>
                                    <h4>Usage Instructions</h4>
                                    <ol style="padding-left: 20px; color: #666;">
                                        <li>Print the barcode on a label</li>
                                        <li>Attach to the equipment</li>
                                        <li>Use barcode scanner to read</li>
                                        <li>Update equipment status as needed</li>
                                    </ol>
                                    
                                    <div style="margin-top: 20px;">
                                        <h4>Recommended Sizes</h4>
                                        <ul style="padding-left: 20px; color: #666;">
                                            <li>Small labels: 1" x 0.5"</li>
                                            <li>Standard labels: 2" x 1"</li>
                                            <li>Large labels: 3" x 1.5"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function printBarcode() {
            window.print();
        }
        
        function downloadBarcode() {
            const svg = document.querySelector('#barcodeDisplay svg');
            if (svg) {
                const svgData = new XMLSerializer().serializeToString(svg);
                const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
                const svgUrl = URL.createObjectURL(svgBlob);
                
                const downloadLink = document.createElement('a');
                downloadLink.href = svgUrl;
                downloadLink.download = 'barcode_<?php echo htmlspecialchars($barcode); ?>.svg';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        }
        
        function copyBarcode() {
            const barcodeText = '<?php echo htmlspecialchars($barcode); ?>';
            navigator.clipboard.writeText(barcodeText).then(function() {
                alert('Barcode text copied to clipboard: ' + barcodeText);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = barcodeText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Barcode text copied to clipboard: ' + barcodeText);
            });
        }
        
        // Auto-focus on barcode input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('barcode').focus();
        });
        
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