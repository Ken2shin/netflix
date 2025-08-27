<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Si ya está autenticado, redirigir
if (isAuthenticated()) {
    redirect('profiles.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $subscription_plan = $_POST['subscription_plan'] ?? 'basico';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, completa todos los campos';
    } elseif (!isValidEmail($email)) {
        $error = 'Por favor, ingresa un email válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $pdo = getConnection();
            
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email ya está registrado';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, subscription_plan, subscription_status) VALUES (?, ?, ?, ?, 'active')");
                
                if ($stmt->execute([$name, $email, $hashedPassword, $subscription_plan])) {
                    $userId = $pdo->lastInsertId();
                    
                    // Iniciar sesión automáticamente
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['is_admin'] = false;
                    
                    redirect('profiles.php');
                } else {
                    $error = 'Error al crear la cuenta';
                }
            }
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
            error_log("Register error: " . $e->getMessage());
        }
    }
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
    $stmt->execute();
    $subscription_plans = $stmt->fetchAll();
} catch (Exception $e) {
    $subscription_plans = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Registrarse</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #000;
            color: #fff;
            min-height: 100vh;
            background-image: url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .register-container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-form {
            background: rgba(0, 0, 0, 0.75);
            padding: 60px 68px 40px;
            border-radius: 4px;
            width: 100%;
            max-width: 450px;
        }

        .netflix-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 3;
        }

        .netflix-logo img {
            height: 40px;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 28px;
            color: #fff;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-input {
            width: 100%;
            height: 50px;
            background: #333;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            padding: 16px 20px 0;
            outline: none;
        }

        .form-input:focus {
            background: #454545;
        }

        .form-label {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #8c8c8c;
            font-size: 16px;
            transition: all 0.2s ease;
            pointer-events: none;
        }

        .form-input:focus + .form-label,
        .form-input:not(:placeholder-shown) + .form-label {
            top: 10px;
            font-size: 11px;
            transform: translateY(0);
        }

        /* Added subscription plan selection styles */
        .plan-selection {
            margin-bottom: 20px;
        }

        .plan-selection h3 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .plan-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .plan-option {
            background: #333;
            border: 2px solid #555;
            border-radius: 4px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .plan-option:hover {
            border-color: #e50914;
        }

        .plan-option.selected {
            border-color: #e50914;
            background: rgba(229, 9, 20, 0.1);
        }

        .plan-option input[type="radio"] {
            display: none;
        }

        .plan-name {
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }

        .plan-price {
            color: #e50914;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .plan-features {
            color: #ccc;
            font-size: 14px;
            line-height: 1.4;
        }

        .register-button {
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

        .register-button:hover {
            background: #f40612;
        }

        .error-message {
            background: #e87c03;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .login-link {
            margin-top: 16px;
            color: #737373;
            font-size: 16px;
            text-align: center;
        }

        .login-link a {
            color: #fff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="netflix-logo">
        <img src="assets/images/netflix-logo.png" alt="Netflix">
    </div>
    
    <div class="register-container">
        <form class="register-form" method="POST">
            <h1 class="form-title">Registrarse</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <input type="text" name="name" class="form-input" placeholder=" " required>
                <label class="form-label">Nombre completo</label>
            </div>
            
            <div class="form-group">
                <input type="email" name="email" class="form-input" placeholder=" " required>
                <label class="form-label">Email</label>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder=" " required>
                <label class="form-label">Contraseña</label>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" class="form-input" placeholder=" " required>
                <label class="form-label">Confirmar contraseña</label>
            </div>
            
            <!-- Added subscription plan selection -->
            <?php if (!empty($subscription_plans)): ?>
            <div class="plan-selection">
                <h3>Elige tu plan</h3>
                <div class="plan-options">
                    <?php foreach ($subscription_plans as $plan): ?>
                        <label class="plan-option" onclick="selectPlan(this)">
                            <input type="radio" name="subscription_plan" value="<?php echo $plan['name']; ?>" 
                                   <?php echo $plan['name'] === 'basico' ? 'checked' : ''; ?>>
                            <div class="plan-name"><?php echo htmlspecialchars($plan['display_name']); ?></div>
                            <div class="plan-price">$<?php echo number_format($plan['price'], 2); ?>/mes</div>
                            <div class="plan-features">
                                <?php 
                                $features = explode(',', $plan['features']);
                                foreach ($features as $feature) {
                                    echo '• ' . trim($feature) . '<br>';
                                }
                                ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="register-button">Registrarse</button>
            
            <div class="login-link">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>.
            </div>
        </form>
    </div>

    <script>
        function selectPlan(element) {
            // Remove selected class from all options
            document.querySelectorAll('.plan-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
        }

        // Set initial selected state
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[type="radio"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.plan-option').classList.add('selected');
            }
        });
    </script>
</body>
</html>
