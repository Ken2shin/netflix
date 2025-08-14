<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'netflix1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Contraseña vacía para XAMPP/WAMP por defecto

// Configuración general
define('SITE_URL', 'http://localhost/streamflixnetflixclone2');
define('SITE_NAME', 'Netflix Clone');
define('MAX_PROFILES_PER_USER', 5);

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Funciones auxiliares
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo "<script>window.location.href = '$url';</script>";
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'is_admin' => $_SESSION['is_admin'] ?? false
    ];
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireAuth();
    if (!$_SESSION['is_admin']) {
        redirect('index.php');
    }
}
?>
