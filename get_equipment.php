<?php
require_once 'config/database.php';
requireLogin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Equipment ID required']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category_name 
        FROM equipment e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $equipment = $stmt->fetch();
    
    if (!$equipment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit;
    }
    
    // If this is a view request, return HTML
    if (isset($_GET['view'])) {
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>Basic Information</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Name:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['name']); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Barcode:</td>
                        <td style="padding: 8px;"><code><?php echo $equipment['barcode']; ?></code></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Category:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['category_name']); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Status:</td>
                        <td style="padding: 8px;">
                            <?php
                            $badgeClass = 'secondary';
                            switch ($equipment['status']) {
                                case 'Available': $badgeClass = 'success'; break;
                                case 'In Use': $badgeClass = 'warning'; break;
                                case 'Maintenance': $badgeClass = 'info'; break;
                                case 'Damaged': $badgeClass = 'danger'; break;
                                case 'Lost': $badgeClass = 'danger'; break;
                            }
                            ?>
                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                <?php echo $equipment['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Location:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['location']) ?: '-'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h4>Technical Details</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Brand:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['brand']) ?: '-'; ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Model:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['model']) ?: '-'; ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Serial Number:</td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($equipment['serial_number']) ?: '-'; ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Purchase Date:</td>
                        <td style="padding: 8px;"><?php echo $equipment['purchase_date'] ? date('M d, Y', strtotime($equipment['purchase_date'])) : '-'; ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px; font-weight: bold;">Purchase Price:</td>
                        <td style="padding: 8px;"><?php echo $equipment['purchase_price'] ? '$' . number_format($equipment['purchase_price'], 2) : '-'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($equipment['description']): ?>
            <div style="margin-top: 20px;">
                <h4>Description</h4>
                <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 0;">
                    <?php echo nl2br(htmlspecialchars($equipment['description'])); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if ($equipment['warranty_expiry']): ?>
            <div style="margin-top: 20px;">
                <h4>Warranty Information</h4>
                <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 0;">
                    <strong>Warranty Expires:</strong> <?php echo date('M d, Y', strtotime($equipment['warranty_expiry'])); ?>
                    <?php 
                    $daysUntilExpiry = (strtotime($equipment['warranty_expiry']) - time()) / (60 * 60 * 24);
                    if ($daysUntilExpiry < 0): ?>
                        <span class="badge badge-danger" style="margin-left: 10px;">Expired</span>
                    <?php elseif ($daysUntilExpiry < 30): ?>
                        <span class="badge badge-warning" style="margin-left: 10px;">Expires Soon</span>
                    <?php else: ?>
                        <span class="badge badge-success" style="margin-left: 10px;">Active</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <h4>System Information</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px; font-weight: bold;">Created:</td>
                    <td style="padding: 8px;"><?php echo date('M d, Y H:i', strtotime($equipment['created_at'])); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px; font-weight: bold;">Last Updated:</td>
                    <td style="padding: 8px;"><?php echo date('M d, Y H:i', strtotime($equipment['updated_at'])); ?></td>
                </tr>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
            <a href="barcode_generator.php?equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-barcode"></i> Generate Barcode
            </a>
            <button class="btn btn-warning" onclick="editEquipment(<?php echo $equipment['id']; ?>); document.getElementById('viewModal').style.display='none';">
                <i class="fas fa-edit"></i> Edit Equipment
            </button>
        </div>
        <?php
        exit;
    }
    
    // Return JSON for edit form
    echo json_encode([
        'success' => true,
        'equipment' => $equipment
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching equipment: ' . $e->getMessage()
    ]);
}
?>