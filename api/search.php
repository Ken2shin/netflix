<?php
require_once '../middleware/auth.php';
require_once '../controllers/APIController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireAuth();

$query = $_GET['q'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query de búsqueda requerido']);
    exit;
}

try {
    $apiController = new APIController();
    $results = $apiController->searchMedia($query);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log('Search API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
