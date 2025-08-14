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

$action = $input['action'] ?? null;
$mediaId = $input['media_id'] ?? null;
$userId = $input['user_id'] ?? null;
$csrfToken = $input['csrf_token'] ?? null;

if (!$action || !$mediaId || !$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción, Media ID y User ID son requeridos']);
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
    
    if ($action === 'add') {
        $result = $apiController->addToWatchlist($mediaId, $userId);
        $message = $result ? 'Agregado a Mi Lista' : 'Error al agregar a la lista';
    } elseif ($action === 'remove') {
        $result = $apiController->removeFromWatchlist($mediaId, $userId);
        $message = $result ? 'Eliminado de Mi Lista' : 'Error al eliminar de la lista';
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit;
    }
    
    echo json_encode([
        'success' => $result,
        'message' => $message,
        'action' => $action,
        'media_id' => $mediaId,
        'added' => $action === 'add' && $result
    ]);
    
} catch (Exception $e) {
    error_log('Watchlist API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
