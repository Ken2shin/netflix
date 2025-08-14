<?php
require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validaciones
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception('Todos los campos son obligatorios');
        }
        
        if (!isValidEmail($email)) {
            throw new Exception('El email no es válido');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Las contraseñas no coinciden');
        }
        
        // Verificar conexión a la base de datos
        $pdo = getConnection();
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            throw new Exception('Este email ya está registrado');
        }
        
        // Crear usuario
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, subscription_type) VALUES (?, ?, ?)");
        $stmt->execute([$email, $hashedPassword, 'basic']);
        
        $userId = $pdo->lastInsertId();
        
        // Crear perfil por defecto
        $stmt = $pdo->prepare("INSERT INTO profiles (user_id, name, avatar) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name, 'avatar1.png']);
        
        // Iniciar sesión automáticamente
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['is_admin'] = false;
        
        redirect('profiles.php');
        
    } catch (Exception $e) {
        $error = 'Error al crear la cuenta: ' . $e->getMessage();
        error_log("Error en registro: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - Netflix</title>
    <link rel="stylesheet" href="assets/css/netflix-auth.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #000;
            color: #fff;
            overflow-x: hidden;
        }

        .netflix-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
            animation: backgroundMove 25s ease-in-out infinite;
        }

        .netflix-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                135deg,
                rgba(0, 0, 0, 0.8) 0%,
                rgba(0, 0, 0, 0.4) 50%,
                rgba(0, 0, 0, 0.8) 100%
            );
            z-index: 1;
            animation: gradientShift 30s ease-in-out infinite;
        }

        @keyframes backgroundMove {
            0%, 100% { transform: scale(1.0) translateX(0px) translateY(0px); }
            25% { transform: scale(1.05) translateX(-20px) translateY(-10px); }
            50% { transform: scale(1.1) translateX(20px) translateY(-20px); }
            75% { transform: scale(1.05) translateX(-10px) translateY(10px); }
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 0.6; }
        }

        .auth-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-form {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 60px 68px 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.8);
        }

        .auth-form h1 {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 28px;
            text-align: left;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-input {
            width: 100%;
            height: 50px;
            background: #333;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            padding: 16px 20px;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            background: #454545;
        }

        .form-input::placeholder {
            color: #8c8c8c;
        }

        .btn-primary {
            width: 100%;
            height: 48px;
            background: #e50914;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background: #f40612;
        }

        .error-message {
            background: #e87c03;
            color: #fff;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .auth-footer {
            margin-top: 16px;
            color: #737373;
            font-size: 16px;
        }

        .auth-footer a {
            color: #fff;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .netflix-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 20;
        }

        .netflix-logo img {
            height: 45px;
        }
    </style>
</head>
<body>
    <div class="netflix-background"></div>
    
    <div class="netflix-logo">
        <img src="assets/images/netflix-logo.png" alt="Netflix">
    </div>

    <div class="auth-container">
        <form class="auth-form" method="POST">
            <h1>Crear cuenta</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <input type="text" name="name" class="form-input" placeholder="Nombre completo" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <input type="email" name="email" class="form-input" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder="Contraseña" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" class="form-input" placeholder="Confirmar contraseña" required>
            </div>
            
            <button type="submit" class="btn-primary">Crear cuenta</button>
            
            <div class="auth-footer">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
            </div>
        </form>
    </div>
</body>
</html>
