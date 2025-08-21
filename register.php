<?php
session_start();

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Conexión directa a la base de datos
        $pdo = new PDO("mysql:host=localhost;port=3306;dbname=netflix1;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validaciones
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un email válido']);
            exit;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit;
        }
        
        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit;
        }
        
        // Verificar estructura de la tabla
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        $hasNameColumn = in_array('name', $columns);
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            exit;
        }
        
        // Insertar usuario adaptándose a la estructura de la tabla
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        if ($hasNameColumn) {
            // Si existe la columna name
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $result = $stmt->execute([$name, $email, $hashedPassword]);
        } else {
            // Si no existe la columna name, solo usar email y password
            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $result = $stmt->execute([$email, $hashedPassword]);
        }
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            
            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $hasNameColumn ? $name : $email;
            $_SESSION['is_admin'] = false;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cuenta creada exitosamente',
                'redirect' => 'profiles.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la cuenta']);
        }
        
    } catch (PDOException $e) {
        error_log("Register error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al crear la cuenta: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Register error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            background: rgba(0,0,0,0.85);
            padding: 60px 68px 40px;
            border-radius: 4px;
            width: 100%;
            max-width: 450px;
            color: white;
        }
        
        .netflix-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            height: 45px;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 28px;
            color: white;
            text-align: center;
        }
        
        .error-message, .success-message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
            display: none;
        }
        
        .error-message {
            background-color: #e87c03;
            color: white;
        }
        
        .success-message {
            background-color: #46d369;
            color: white;
        }
        
        .input-group {
            margin-bottom: 16px;
        }
        
        input {
            width: 100%;
            height: 50px;
            background: #333;
            border: none;
            border-radius: 4px;
            color: white;
            padding: 16px 20px;
            font-size: 16px;
        }
        
        input::placeholder {
            color: #8c8c8c;
        }
        
        input:focus {
            outline: none;
            background: #454545;
        }
        
        .register-button {
            width: 100%;
            height: 50px;
            background: #e50914;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-button:hover {
            background: #f40612;
        }
        
        .register-button:disabled {
            background: #666;
            cursor: not-allowed;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .register-footer {
            margin-top: 16px;
            color: #737373;
            font-size: 16px;
            text-align: center;
        }
        
        .register-footer a {
            color: white;
            text-decoration: none;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <img src="assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
    
    <div class="register-container">
        <h1>Crear cuenta</h1>
        
        <div id="error-message" class="error-message"></div>
        <div id="success-message" class="success-message"></div>
        
        <form id="registerForm">
            <div class="input-group">
                <input type="text" name="name" id="name" placeholder="Nombre completo" required>
            </div>
            
            <div class="input-group">
                <input type="email" name="email" id="email" placeholder="Email" required>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
            </div>
            
            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmar contraseña" required>
            </div>
            
            <button type="submit" class="register-button" id="submitBtn">
                <span class="button-text">Crear cuenta</span>
                <span class="loading-spinner" id="loadingSpinner"></span>
            </button>
        </form>
        
        <div class="register-footer">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </div>
    </div>

    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const buttonText = submitBtn.querySelector('.button-text');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const errorDiv = document.getElementById('error-message');
        const successDiv = document.getElementById('success-message');
        
        // Mostrar loading
        submitBtn.disabled = true;
        buttonText.style.display = 'none';
        loadingSpinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        const formData = new FormData(this);
        
        fetch('register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successDiv.textContent = data.message;
                successDiv.style.display = 'block';
                setTimeout(() => {
                    window.location.href = data.redirect || 'profiles.php';
                }, 1500);
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.disabled = false;
            buttonText.style.display = 'inline-block';
            loadingSpinner.style.display = 'none';
        });
    });
    </script>
</body>
</html>
