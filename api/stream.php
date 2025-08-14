<?php
require_once '../middleware/auth.php';
require_once '../controllers/APIController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$mediaId = $input['media_id'] ?? null;
$userId = $input['user_id'] ?? null;
$csrfToken = $input['csrf_token'] ?? null;

if (!$mediaId || !$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Media ID y User ID son requeridos']);
    exit;
}

if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

if ($userId != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $apiController = new APIController();
    $result = $apiController->startStreaming($mediaId, $userId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Streaming iniciado',
            'stream_url' => "/play/{$mediaId}",
            'media_id' => $mediaId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al iniciar streaming'
        ]);
    }
} catch (Exception $e) {
    error_log('Stream API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
