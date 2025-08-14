<?php
require_once 'config/config.php';

try {
    require_once 'controllers/AuthController.php';
    
    $authController = new AuthController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->processRegister();
    } else {
        $authController->showRegister();
    }
} catch (Exception $e) {
    error_log("Register page error: " . $e->getMessage());
    
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
        exit;
    }
    
    // Si no es AJAX, mostrar página de error
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error - Netflix</title>
        <style>
            body { font-family: Arial, sans-serif; background: #000; color: #fff; text-align: center; padding: 50px; }
            .error { background: #e50914; padding: 20px; border-radius: 5px; display: inline-block; }
            a { color: #fff; text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h2>Error de conexión</h2>
            <p>No se pudo conectar a la base de datos.</p>
            <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
            <p><a href='register.php'>Intentar de nuevo</a></p>
        </div>
    </body>
    </html>";
    exit;
}
?>
