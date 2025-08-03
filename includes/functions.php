<?php
require_once 'config/database.php';

// Email notification function
function sendEmail($to, $subject, $message) {
    $headers = "From: LNHS Portal <admin@lnhs.edu.ph>\r\n";
    $headers .= "Reply-To: admin@lnhs.edu.ph\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Create notification
function createNotification($user_id, $title, $message, $type = 'info', $request_id = null) {
    $conn = getDBConnection();
    $sql = "INSERT INTO notifications (user_id, request_id, title, message, type) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$user_id, $request_id, $title, $message, $type]);
}

// Send notification email and create notification record
function sendNotification($user_id, $title, $message, $type = 'info', $request_id = null) {
    // Create notification record
    createNotification($user_id, $title, $message, $type, $request_id);
    
    // Get user email
    $conn = getDBConnection();
    $sql = "SELECT email, first_name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $email_subject = "LNHS Portal - " . $title;
        $email_body = "
        <html>
        <body>
            <h2>LNHS Documents Request Portal</h2>
            <p>Hello " . htmlspecialchars($user['first_name']) . ",</p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <br>
            <p>Best regards,<br>LNHS Administration</p>
        </body>
        </html>";
        
        sendEmail($user['email'], $email_subject, $email_body);
    }
}

// File upload function
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Get document types
function getDocumentTypes() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM document_types WHERE status = 'active' ORDER BY document_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get request status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'processing': return 'bg-info';
        case 'approved': return 'bg-success';
        case 'denied': return 'bg-danger';
        case 'ready_for_pickup': return 'bg-primary';
        case 'completed': return 'bg-dark';
        default: return 'bg-secondary';
    }
}

// Format status text
function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}

// Security function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate random password
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Get unread notifications count
function getUnreadNotificationsCount($user_id) {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Mark notification as read
function markNotificationAsRead($notification_id, $user_id) {
    $conn = getDBConnection();
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$notification_id, $user_id]);
}
?>