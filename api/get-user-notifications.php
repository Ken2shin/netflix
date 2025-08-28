<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    requireAuth();
    
    $currentUser = getCurrentUser();
    $conn = getConnection();
    
    // Get user notifications with admin notification details
    $stmt = $conn->prepare("
        SELECT 
            un.id as user_notification_id,
            un.is_read,
            un.read_at,
            un.created_at as received_at,
            an.id as notification_id,
            an.title,
            an.message,
            an.type,
            an.expires_at,
            u.name as admin_name
        FROM user_notifications un
        JOIN admin_notifications an ON un.notification_id = an.id
        LEFT JOIN users u ON an.created_by = u.id
        WHERE un.user_id = ? 
        AND (an.expires_at IS NULL OR an.expires_at > NOW())
        AND an.is_active = TRUE
        ORDER BY un.created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute([$currentUser['id']]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count
        FROM user_notifications un
        JOIN admin_notifications an ON un.notification_id = an.id
        WHERE un.user_id = ? 
        AND un.is_read = FALSE
        AND (an.expires_at IS NULL OR an.expires_at > NOW())
        AND an.is_active = TRUE
    ");
    
    $stmt->execute([$currentUser['id']]);
    $unreadCount = $stmt->fetch()['unread_count'];
    
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
