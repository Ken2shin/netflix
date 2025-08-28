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
    // Configurar parámetros de sesión antes de iniciar
    ini_set('session.cookie_lifetime', 86400); // 24 horas
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_start();
    
    // Debug: Log session start
    error_log("Session started. Session ID: " . session_id());
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
    
    if (isset($_SESSION['is_admin'])) {
        return (bool)$_SESSION['is_admin'];
    }
    
    try {
        $user = getCurrentUser();
        if ($user && isset($user['is_admin'])) {
            $_SESSION['is_admin'] = $user['is_admin']; // Cache in session
            return (bool)$user['is_admin'];
        }
        return false;
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
    error_log("requireAdmin() called");
    error_log("Session data: " . print_r($_SESSION, true));
    
    if (!isAuthenticated()) {
        error_log("User not authenticated, redirecting to login");
        header('Location: login.php');
        exit();
    }
    
    if (!isAdmin()) {
        error_log("User authenticated but not admin. User ID: " . ($_SESSION['user_id'] ?? 'none') . ", is_admin: " . ($_SESSION['is_admin'] ?? 'not set'));
        header('Location: dashboard.php');
        exit();
    }
    
    error_log("Admin access granted for user: " . ($_SESSION['user_email'] ?? 'unknown'));
}

?>
