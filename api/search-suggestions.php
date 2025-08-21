<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit();
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, title, type, genre, release_year, poster_url
        FROM content 
        WHERE title LIKE ? OR genre LIKE ? 
        ORDER BY view_count DESC 
        LIMIT 8
    ");
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    $suggestions = [];
    foreach ($results as $result) {
        $suggestions[] = [
            'id' => $result['id'],
            'title' => $result['title'],
            'type' => $result['type'],
            'genre' => $result['genre'],
            'year' => $result['release_year'],
            'poster' => $result['poster_url'] ?: '/placeholder.svg?height=100&width=70&query=' . urlencode($result['title'])
        ];
    }
    
    echo json_encode(['suggestions' => $suggestions]);
    
} catch (Exception $e) {
    error_log("Error en search-suggestions: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
