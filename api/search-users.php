<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $query = trim($input['query'] ?? '');
    
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE (name LIKE ? OR email LIKE ?) AND is_admin = FALSE LIMIT 10");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $users = $stmt->fetchAll();
    
    echo json_encode($users);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
