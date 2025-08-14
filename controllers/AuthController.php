<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Profile.php';

class AuthController {
    private $db;
    private $user;
    private $profile;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->user = new User($database);
            $this->profile = new Profile($database);
        } catch (Exception $e) {
            error_log("AuthController initialization error: " . $e->getMessage());
            throw new Exception("Error de inicialización del controlador");
        }
    }

    public function showLogin() {
        if (isLoggedIn()) {
            redirect('profiles.php');
        }
        
        $csrf_token = generateCSRFToken();
        include __DIR__ . '/../views/auth/login.php';
    }

    public function processLogin() {
        header('Content-Type: application/json');
        
        try {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';

            // Verificar token CSRF
            if (!verifyCSRFToken($csrf_token)) {
                echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
                return;
            }

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos']);
                return;
            }

            if (!isValidEmail($email)) {
                echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un email válido']);
                return;
            }

            $user = $this->user->login($email, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Limpiar token CSRF después del login exitoso
                unset($_SESSION['csrf_token']);
                
                echo json_encode(['success' => true, 'message' => 'Login exitoso', 'redirect' => 'profiles.php']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function showRegister() {
        if (isLoggedIn()) {
            redirect('profiles.php');
        }
        
        $csrf_token = generateCSRFToken();
        include __DIR__ . '/../views/auth/register.php';
    }

    public function processRegister() {
        header('Content-Type: application/json');
        
        try {
            $name = sanitize($_POST['name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';

            // Verificar token CSRF
            if (!verifyCSRFToken($csrf_token)) {
                echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
                return;
            }

            // Validaciones
            if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos']);
                return;
            }

            if (!isValidEmail($email)) {
                echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un email válido']);
                return;
            }

            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
                return;
            }

            if ($password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
                return;
            }

            $result = $this->user->register($name, $email, $password);
            
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin'] = false;
                
                // Crear perfil principal automáticamente
                $this->profile->create($result['user_id'], $name, 'avatar1.png');
                
                // Limpiar token CSRF después del registro exitoso
                unset($_SESSION['csrf_token']);
                
                echo json_encode(['success' => true, 'message' => 'Registro exitoso', 'redirect' => 'profiles.php']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function logout() {
        session_destroy();
        redirect('login.php');
    }
}
?>
