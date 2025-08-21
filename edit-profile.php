<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$userId = getCurrentUserId();
$message = '';
$messageType = '';

// Obtener datos del usuario actual
try {
    $db = getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('login.php');
    }
} catch (Exception $e) {
    error_log("Error obteniendo usuario: " . $e->getMessage());
    redirect('dashboard.php');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validaciones básicas
        if (empty($name) || empty($email)) {
            throw new Exception('El nombre y email son obligatorios');
        }
        
        if (!isValidEmail($email)) {
            throw new Exception('El email no es válido');
        }
        
        // Verificar si el email ya existe (excepto el actual)
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Este email ya está en uso');
        }
        
        // Si se quiere cambiar la contraseña
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                throw new Exception('Debes ingresar tu contraseña actual');
            }
            
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('La contraseña actual es incorrecta');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Las contraseñas nuevas no coinciden');
            }
            
            // Actualizar con nueva contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $hashedPassword, $userId]);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $userId]);
        }
        
        // Actualizar sesión
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        $message = 'Perfil actualizado correctamente';
        $messageType = 'success';
        
        // Recargar datos del usuario
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Netflix</title>
    <link rel="stylesheet" href="assets/css/netflix.css">
    <link rel="stylesheet" href="assets/css/netflix-profiles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-profile-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
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
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 5px;
            background: #333;
            color: white;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            background: #444;
        }
        
        .password-section {
            border-top: 1px solid #444;
            padding-top: 30px;
            margin-top: 30px;
        }
        
        .password-section h3 {
            color: white;
            margin-bottom: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #e50914;
            color: white;
        }
        
        .btn-primary:hover {
            background: #f40612;
        }
        
        .btn-secondary {
            background: #333;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #444;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
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
    <?php include 'views/partials/header.php'; ?>
    
    <div class="edit-profile-container">
        <div class="edit-profile-header">
            <h1>Editar Perfil</h1>
            <p style="color: #999;">Actualiza tu información personal</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="password-section">
                <h3>Cambiar contraseña</h3>
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
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <script>
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword && !currentPassword) {
                e.preventDefault();
                alert('Debes ingresar tu contraseña actual para cambiarla');
                return;
            }
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas nuevas no coinciden');
                return;
            }
            
            if (newPassword && newPassword.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return;
            }
        });
    </script>
</body>
</html>
