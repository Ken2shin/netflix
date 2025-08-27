<?php
// Middleware de autenticación

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    
    try {
        $conn = getConnection();
        $userId = $_SESSION['user_id'];
        
        // Verificar si la columna is_admin existe
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'is_admin'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // La columna existe, verificar si es admin
            $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || $user['is_admin'] != 1) {
                header('Location: dashboard.php');
                exit();
            }
        }
        // Si la columna no existe, permitir acceso por ahora
        
    } catch (Exception $e) {
        error_log("Error en requireAdmin: " . $e->getMessage());
        // En caso de error, redirigir al dashboard principal
        header('Location: dashboard.php');
        exit();
    }
}

function requireProfile() {
    requireAuth();
    // Por ahora solo verificamos autenticación básica
}

function isLoggedIn() {
    return isAuthenticated();
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? '';
}
?>
