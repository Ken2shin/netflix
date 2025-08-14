<?php
session_start();
require_once 'config/database.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: profiles.php');
    exit;
}

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => 'profiles.php']);
                    exit;
                }
                
                header('Location: profiles.php');
                exit;
            } else {
                $error = 'Email o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
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

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
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
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo animado de Netflix */
        .netflix-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            animation: slowZoom 20s ease-in-out infinite alternate, parallaxMove 30s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes slowZoom {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(1.1);
            }
        }

        @keyframes parallaxMove {
            0% {
                background-position: center center;
            }
            25% {
                background-position: 20% center;
            }
            50% {
                background-position: center 20%;
            }
            75% {
                background-position: 80% center;
            }
            100% {
                background-position: center 80%;
            }
        }

        .header {
            padding: 20px 60px;
            position: relative;
            z-index: 10;
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
            position: relative;
            z-index: 10;
        }

        .auth-form {
            background: rgba(0, 0, 0, 0.85);
            padding: 60px 68px 40px;
            border-radius: 4px;
            width: 100%;
            max-width: 450px;
            color: white;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .auth-form h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 28px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group input {
            width: 100%;
            height: 50px;
            background: rgba(51, 51, 51, 0.8);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            padding: 16px 20px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .input-group input::placeholder {
            color: #8c8c8c;
        }

        .input-group input:focus {
            outline: none;
            background: rgba(69, 69, 69, 0.9);
            box-shadow: 0 0 10px rgba(229, 9, 20, 0.3);
        }

        .auth-button {
            width: 100%;
            height: 48px;
            background: linear-gradient(45deg, #e50914, #f40612);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
        }

        .auth-button:hover {
            background: linear-gradient(45deg, #f40612, #e50914);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .auth-button:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            background: rgba(232, 124, 3, 0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .auth-footer {
            margin-top: 16px;
            color: #737373;
            font-size: 16px;
        }

        .auth-footer a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            text-decoration: underline;
            color: #e50914;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 740px) {
            .header {
                padding: 20px;
            }
            
            .auth-form {
                padding: 40px 28px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="netflix-background"></div>
    
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
            
            <form id="loginForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email o número de teléfono" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Contraseña" required>
                </div>
                
                <button type="submit" class="auth-button" id="submitBtn">
                    <span class="button-text">Iniciar sesión</span>
                    <span class="loading-spinner" style="display: none;">⟳</span>
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
        const buttonText = submitBtn.querySelector('.button-text');
        const loadingSpinner = submitBtn.querySelector('.loading-spinner');
        const errorDiv = document.getElementById('error-message');
        
        submitBtn.disabled = true;
        buttonText.style.display = 'none';
        loadingSpinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';
        
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
                window.location.href = data.redirect || 'profiles.php';
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
