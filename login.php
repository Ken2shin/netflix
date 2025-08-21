<?php
session_start();

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password_input = $_POST['password'] ?? '';
    
    if ($email && $password_input) {
        try {
            // Conexión directa a netflix1
            $pdo = new PDO("mysql:host=localhost;port=3306;dbname=netflix1;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar estructura de tabla users
            $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            
            // Crear query basado en columnas existentes
            $selectFields = ['id', 'email', 'password'];
            $hasName = in_array('name', $columns);
            $hasIsAdmin = in_array('is_admin', $columns);
            
            if ($hasName) $selectFields[] = 'name';
            if ($hasIsAdmin) $selectFields[] = 'is_admin';
            
            $query = "SELECT " . implode(', ', $selectFields) . " FROM users WHERE email = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password_input, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $hasName ? $user['name'] : 'Usuario';
                $_SESSION['is_admin'] = $hasIsAdmin ? ($user['is_admin'] ?? 0) : 0;
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
                    exit;
                }
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = 'Error de conexión. Por favor, intenta de nuevo.';
            error_log("Login error: " . $e->getMessage());
        }
    } else {
        $error = 'Por favor completa todos los campos';
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('assets/images/netflix-background.jpg') center/cover;
            display: flex;
            flex-direction: column;
        }

        .header {
            padding: 20px 60px;
        }

        .logo {
            height: 45px;
        }

        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .auth-form {
            background: rgba(0, 0, 0, 0.85);
            padding: 60px 68px 40px;
            border-radius: 4px;
            width: 100%;
            max-width: 450px;
            color: white;
        }

        .auth-form h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 28px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group input {
            width: 100%;
            height: 50px;
            background: #333;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            padding: 16px 20px;
        }

        .input-group input::placeholder {
            color: #8c8c8c;
        }

        .input-group input:focus {
            outline: none;
            background: #454545;
        }

        .auth-button {
            width: 100%;
            height: 48px;
            background: #e50914;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
        }

        .auth-button:hover {
            background: #f40612;
        }

        .auth-button:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .error-message {
            background: #e87c03;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .success-message {
            background: #2e7d32;
            color: white;
            padding: 10px 20px;
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
            color: white;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 740px) {
            .header { padding: 20px; }
            .auth-form { padding: 40px 28px; margin: 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/images/netflix-logo.png" alt="Netflix" class="logo">
    </div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h1>Iniciar sesión</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div id="error-message" class="error-message" style="display: none;"></div>
            <div id="success-message" class="success-message" style="display: none;"></div>
            
            <form id="loginForm" method="POST">
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Contraseña" required>
                </div>
                
                <button type="submit" class="auth-button" id="submitBtn">
                    Iniciar sesión
                </button>
            </form>
            
            <div class="auth-footer">
                <p>¿Nuevo en Netflix? <a href="register.php">Regístrate ahora</a></p>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const errorDiv = document.getElementById('error-message');
        const successDiv = document.getElementById('success-message');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Iniciando...';
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        const formData = new FormData(this);
        
        fetch('login.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successDiv.textContent = 'Login exitoso, redirigiendo...';
                successDiv.style.display = 'block';
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            errorDiv.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Iniciar sesión';
        });
    });
    </script>
</body>
</html>
