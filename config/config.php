<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'netflix1');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de la aplicación
define('APP_NAME', 'Netflix Clone');
define('APP_URL', 'http://localhost');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar si el usuario es administrador
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Función para requerir autenticación
function requireLogin() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

// Función para requerir perfil (simplificada)
function requireProfile() {
    requireLogin();
    // Por ahora solo verificamos que esté logueado
    // En el futuro se puede expandir para verificar perfil específico
}

// Función para limpiar datos de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para generar hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para redirigir
function redirect($url) {
    header("Location: $url");
    exit();
}

// Función para mostrar errores en desarrollo
function showError($message) {
    error_log($message);
    if (defined('DEBUG') && DEBUG) {
        echo "<div style='background: #f44336; color: white; padding: 10px; margin: 10px; border-radius: 4px;'>Error: $message</div>";
    }
}

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Crear directorio de logs si no existe
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
?>
