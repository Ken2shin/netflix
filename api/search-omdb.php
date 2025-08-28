<?php
header('Content-Type: application/json');
require_once '../services/OMDBService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'movie'; // movie, series, episode

if (empty($query)) {
    echo json_encode(['error' => 'Query parameter is required']);
    exit;
}

try {
    $omdbService = new OMDBService();
    $results = $omdbService->searchMovies($query, $type);
    
    if ($results && isset($results['Search'])) {
        echo json_encode([
            'success' => true,
            'results' => $results['Search'],
            'totalResults' => $results['totalResults'] ?? 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No results found',
            'results' => []
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>
