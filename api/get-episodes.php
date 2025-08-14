<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$seriesId = $_GET['series_id'] ?? null;
$seasonNumber = $_GET['season'] ?? null;

if (!$seriesId || !$seasonNumber) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros faltantes']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    // Obtener episodios de la temporada
    $stmt = $pdo->prepare("
        SELECT e.*, s.season_number 
        FROM episodes e
        JOIN seasons s ON e.season_id = s.id
        WHERE s.content_id = ? AND s.season_number = ?
        ORDER BY e.episode_number ASC
    ");
    
    $stmt->execute([$seriesId, $seasonNumber]);
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'episodes' => $episodes
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>
