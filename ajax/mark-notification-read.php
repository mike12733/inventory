<?php
require_once '../config/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$notification_id = intval($input['notification_id'] ?? 0);
$user_id = getCurrentUserId();

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    $result = markNotificationAsRead($notification_id, $user_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating notification']);
}
?>