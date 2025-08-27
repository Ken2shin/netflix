<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'netflix1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// Configuración de la aplicación
define('APP_NAME', 'StreamFlix');
define('APP_URL', 'http://localhost:3000');

define('MAX_PROFILES_PER_USER', 5);

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar autenticación
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isLoggedIn() {
    return isAuthenticated();
}

// Función para requerir autenticación
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

function requireLogin() {
    return requireAuth();
}

// Función para requerir perfil
function requireProfile() {
    requireAuth();
    if (!isset($_SESSION['profile_id'])) {
        header('Location: profiles.php');
        exit();
    }
}

// Función para redirigir
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Función para limpiar entrada
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para obtener el ID del usuario actual
function getCurrentUserId() {
    if (!isAuthenticated()) {
        return null;
    }
    return $_SESSION['user_id'] ?? null;
}

function isAdmin() {
    if (!isAuthenticated()) {
        return false;
    }
    
    try {
        $user = getCurrentUser();
        return $user && isset($user['is_admin']) && $user['is_admin'];
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

// Función para obtener usuario actual
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

// Función para obtener perfil actual
function getCurrentProfile() {
    if (!isset($_SESSION['profile_id'])) {
        return ['name' => 'Usuario', 'avatar' => 'avatar1.png'];
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$_SESSION['profile_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        return $profile ?: ['name' => 'Usuario', 'avatar' => 'avatar1.png'];
    } catch (Exception $e) {
        return ['name' => 'Usuario', 'avatar' => 'avatar1.png'];
    }
}

// Función para requerir administrador
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

?>
