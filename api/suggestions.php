<?php
session_start();
require_once '../controllers/SearchController.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$searchController = new SearchController();
$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$suggestions = $searchController->getSuggestions($query);
echo json_encode($suggestions);
?>
