<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = $input['notification_id'] ?? null;
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID required']);
        exit;
    }
    
    $currentUser = getCurrentUser();
    $conn = getConnection();
    
    // Mark notification as read
    $stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = TRUE, read_at = NOW() 
        WHERE user_id = ? AND id = ?
    ");
    
    $stmt->execute([$currentUser['id'], $notificationId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
