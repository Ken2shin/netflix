<?php
require_once __DIR__ . '/../config/config.php';

// Verificar si el usuario está autenticado
if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }
    }
}

// Verificar si el usuario tiene un perfil seleccionado
if (!function_exists('requireProfile')) {
    function requireProfile() {
        requireAuth();
        if (!isset($_SESSION['current_profile_id'])) {
            redirect('/select-profile.php');
        }
    }
}

// Verificar si el usuario es administrador
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireAuth();
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            redirect('/home.php');
        }
    }
}

// Obtener usuario actual
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting current user: ' . $e->getMessage());
            return null;
        }
    }
}

// Obtener perfil actual
if (!function_exists('getCurrentProfile')) {
    function getCurrentProfile() {
        if (!isset($_SESSION['current_profile_id'])) {
            return null;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM profiles WHERE id = ? AND user_id = ?");
            $stmt->execute([$_SESSION['current_profile_id'], $_SESSION['user_id']]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting current profile: ' . $e->getMessage());
            return null;
        }
    }
}

// Verificar permisos de perfil
if (!function_exists('checkProfilePermission')) {
    function checkProfilePermission($profileId) {
        if (!isLoggedIn()) {
            return false;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT id FROM profiles WHERE id = ? AND user_id = ?");
            $stmt->execute([$profileId, $_SESSION['user_id']]);
            
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log('Error checking profile permission: ' . $e->getMessage());
            return false;
        }
    }
}

// Limpiar sesión
if (!function_exists('clearSession')) {
    function clearSession() {
        session_unset();
        session_destroy();
        session_start();
    }
}

// Verificar token de API
if (!function_exists('verifyAPIToken')) {
    function verifyAPIToken($token) {
        return !empty($token) && strlen($token) > 20;
    }
}
?>
