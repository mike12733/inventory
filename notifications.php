<?php
$page_title = "Notifications";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $conn = getDBConnection();
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$user_id])) {
        setFlashMessage('success', 'All notifications marked as read.');
    }
    header('Location: notifications.php');
    exit();
}

// Get all notifications
$conn = getDBConnection();
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-bell me-2"></i>Notifications
                </h2>
                <p class="text-muted mb-0">Your system notifications and updates</p>
            </div>
            <div>
                <?php if (!empty($notifications)): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                            <i class="fas fa-check-double me-2"></i>Mark All Read
                        </button>
                    </form>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if ($notifications): ?>
            <div class="card">
                <div class="card-body p-0">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item border-bottom p-4 <?php echo !$notification['is_read'] ? 'bg-light border-start border-primary border-3' : ''; ?>">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon me-3">
                                    <?php
                                    $icon_class = '';
                                    $icon_color = '';
                                    switch ($notification['type']) {
                                        case 'success':
                                            $icon_class = 'fa-check-circle';
                                            $icon_color = 'text-success';
                                            break;
                                        case 'warning':
                                            $icon_class = 'fa-exclamation-triangle';
                                            $icon_color = 'text-warning';
                                            break;
                                        case 'error':
                                            $icon_class = 'fa-times-circle';
                                            $icon_color = 'text-danger';
                                            break;
                                        default:
                                            $icon_class = 'fa-info-circle';
                                            $icon_color = 'text-info';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon_class; ?> fa-2x <?php echo $icon_color; ?>"></i>
                                </div>
                                
                                <div class="notification-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="mb-2 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h5>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </small>
                                            <?php if (!$notification['is_read']): ?>
                                                <br><span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2 text-muted">
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </p>
                                    
                                    <?php if ($notification['request_id']): ?>
                                        <a href="view-request.php?id=<?php echo $notification['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View Request
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-check me-1"></i>Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">No Notifications</h4>
                    <p class="text-muted">You don't have any notifications yet. When you submit document requests or receive updates, they'll appear here.</p>
                    <a href="request-document.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Submit a Request
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('ajax/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php require_once 'includes/footer.php'; ?>