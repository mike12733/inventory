<?php
require_once '../config/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = getCurrentUserId();

try {
    $conn = getDBConnection();
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // Format notifications for display
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $formatted_notifications[] = [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'is_read' => (bool)$notification['is_read'],
            'created_at' => date('M j, g:i A', strtotime($notification['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading notifications']);
}
?>