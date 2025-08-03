<?php
require_once 'config/database.php';
requireLogin();

if (!isset($_GET['id'])) {
    echo '<p>Log ID required</p>';
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT al.*, a.full_name, a.email
        FROM admin_logs al 
        JOIN admins a ON al.admin_id = a.id 
        WHERE al.id = ?
    ");
    $stmt->execute([$id]);
    $log = $stmt->fetch();
    
    if (!$log) {
        echo '<p>Log not found</p>';
        exit;
    }
    
    ?>
    <div style="margin-bottom: 20px;">
        <h4>Basic Information</h4>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold; width: 150px;">Admin:</td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($log['full_name']); ?> (<?php echo htmlspecialchars($log['email']); ?>)</td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold;">Action:</td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($log['action']); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold;">Table Affected:</td>
                <td style="padding: 8px;"><?php echo $log['table_affected'] ?: 'N/A'; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold;">Record ID:</td>
                <td style="padding: 8px;"><?php echo $log['record_id'] ?: 'N/A'; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold;">Date/Time:</td>
                <td style="padding: 8px;"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold;">IP Address:</td>
                <td style="padding: 8px;"><?php echo $log['ip_address'] ?: 'N/A'; ?></td>
            </tr>
        </table>
    </div>
    
    <?php if ($log['old_values']): ?>
        <div style="margin-bottom: 20px;">
            <h4>Previous Values</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto;">
                <?php 
                $oldValues = json_decode($log['old_values'], true);
                if ($oldValues) {
                    echo '<pre>' . htmlspecialchars(json_encode($oldValues, JSON_PRETTY_PRINT)) . '</pre>';
                } else {
                    echo htmlspecialchars($log['old_values']);
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($log['new_values']): ?>
        <div style="margin-bottom: 20px;">
            <h4>New Values</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto;">
                <?php 
                $newValues = json_decode($log['new_values'], true);
                if ($newValues) {
                    echo '<pre>' . htmlspecialchars(json_encode($newValues, JSON_PRETTY_PRINT)) . '</pre>';
                } else {
                    echo htmlspecialchars($log['new_values']);
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($log['user_agent']): ?>
        <div style="margin-bottom: 20px;">
            <h4>User Agent</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 12px; word-break: break-all;">
                <?php echo htmlspecialchars($log['user_agent']); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <button class="btn btn-secondary" onclick="document.getElementById('logDetailsModal').style.display='none'">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading log details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>