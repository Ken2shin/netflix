<?php
header('Content-Type: application/json');
require_once '../services/OMDBService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$imdbId = $_GET['imdb_id'] ?? '';

if (empty($imdbId)) {
    echo json_encode(['error' => 'IMDB ID is required']);
    exit;
}

try {
    $omdbService = new OMDBService();
    $details = $omdbService->getMovieDetails($imdbId);
    
    if ($details && $details['Response'] === 'True') {
        echo json_encode([
            'success' => true,
            'data' => $details
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $details['Error'] ?? 'Movie not found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get details: ' . $e->getMessage()
    ]);
}
?>
