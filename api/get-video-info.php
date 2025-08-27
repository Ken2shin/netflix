<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT video_url, video_platform FROM content WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($content) {
        echo json_encode([
            'success' => true,
            'video_url' => $content['video_url'],
            'platform' => $content['video_platform'] ?? 'direct'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contenido no encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
