<?php
session_start();
require_once '../middleware/auth.php';
require_once '../models/Watchlist.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isAuthenticated() || !isset($_SESSION['profile_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$contentId = $input['content_id'] ?? null;

if (!$contentId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de contenido requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $watchlist = new Watchlist($db);
    
    $profileId = $_SESSION['profile_id'];
    $isInWatchlist = $watchlist->isInWatchlist($profileId, $contentId);
    
    if ($isInWatchlist) {
        $result = $watchlist->removeFromWatchlist($profileId, $contentId);
        echo json_encode([
            'success' => $result,
            'added' => false,
            'message' => 'Eliminado de Mi Lista'
        ]);
    } else {
        $result = $watchlist->addToWatchlist($profileId, $contentId);
        echo json_encode([
            'success' => $result,
            'added' => true,
            'message' => 'Agregado a Mi Lista'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
