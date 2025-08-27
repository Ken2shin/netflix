<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Obtener datos del usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validaciones
        if (empty($name)) {
            $error = 'El nombre es requerido';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email válido es requerido';
        } elseif ($email !== $user['email']) {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = 'Este email ya está en uso';
            }
        }
        
        // Si se quiere cambiar contraseña
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $error = 'Debes ingresar tu contraseña actual';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $error = 'Contraseña actual incorrecta';
            } elseif (strlen($newPassword) < 6) {
                $error = 'La nueva contraseña debe tener al menos 6 caracteres';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden';
            }
        }
        
        if (empty($error)) {
            try {
                if (!empty($newPassword)) {
                    // Actualizar con nueva contraseña
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $email, $hashedPassword, $userId]);
                } else {
                    // Actualizar sin cambiar contraseña
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $email, $userId]);
                }
                
                // Actualizar sesión
                $_SESSION['user_name'] = $name;
                
                $message = 'Perfil actualizado correctamente';
                
                // Recargar datos del usuario
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
            } catch (PDOException $e) {
                $error = 'Error al actualizar el perfil';
                error_log("Error actualizando perfil: " . $e->getMessage());
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error en edit-profile: " . $e->getMessage());
    $error = 'Error interno del servidor';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Netflix</title>
    <link rel="stylesheet" href="assets/css/netflix.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .edit-profile-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
        }
        
        .edit-profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .edit-profile-header h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #333;
            border-radius: 4px;
            background: #333;
            color: white;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #e50914;
        }
        
        .password-section {
            border-top: 1px solid #333;
            padding-top: 30px;
            margin-top: 30px;
        }
        
        .password-section h3 {
            color: white;
            margin-bottom: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #e50914;
            color: white;
        }
        
        .btn-secondary {
            background: #333;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Comentar header temporalmente para debug -->
    <!-- <?php include 'views/partials/header.php'; ?> -->
    
    <div class="edit-profile-container">
        <div class="edit-profile-header">
            <h1>Editar Perfil</h1>
            <p style="color: #999;">Actualiza tu información personal</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="password-section">
                <h3>Cambiar Contraseña</h3>
                <p style="color: #999; margin-bottom: 20px;">Deja estos campos vacíos si no quieres cambiar tu contraseña</p>
                
                <div class="form-group">
                    <label for="current_password">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar Cambios
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <script src="assets/js/netflix.js"></script>
</body>
</html>
