<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['profile_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$contentId = $input['content_id'] ?? null;
$rating = $input['rating'] ?? null;

if (!$contentId || !$rating || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    // Verificar si ya existe una calificación
    $stmt = $pdo->prepare("
        SELECT id FROM ratings 
        WHERE profile_id = ? AND content_id = ?
    ");
    $stmt->execute([$_SESSION['profile_id'], $contentId]);
    $existingRating = $stmt->fetch();
    
    if ($existingRating) {
        // Actualizar calificación existente
        $stmt = $pdo->prepare("
            UPDATE ratings 
            SET rating = ?, updated_at = NOW() 
            WHERE profile_id = ? AND content_id = ?
        ");
        $stmt->execute([$rating, $_SESSION['profile_id'], $contentId]);
    } else {
        // Crear nueva calificación
        $stmt = $pdo->prepare("
            INSERT INTO ratings (profile_id, content_id, rating, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$_SESSION['profile_id'], $contentId, $rating]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>
