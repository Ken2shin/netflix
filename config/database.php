<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Intentar diferentes configuraciones de conexión
            $configs = [
                // Configuración 1: Sin contraseña (XAMPP/WAMP por defecto)
                [
                    'host' => DB_HOST,
                    'port' => DB_PORT,
                    'user' => DB_USER,
                    'pass' => '',
                    'dbname' => DB_NAME
                ],
                // Configuración 2: Con contraseña vacía explícita
                [
                    'host' => DB_HOST,
                    'port' => DB_PORT,
                    'user' => DB_USER,
                    'pass' => null,
                    'dbname' => DB_NAME
                ],
                // Configuración 3: Puerto estándar 3306
                [
                    'host' => DB_HOST,
                    'port' => '3306',
                    'user' => DB_USER,
                    'pass' => '',
                    'dbname' => DB_NAME
                ]
            ];
            
            $lastError = '';
            
            foreach ($configs as $config) {
                try {
                    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
                    
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ];
                    
                    $this->connection = new PDO($dsn, $config['user'], $config['pass'], $options);
                    
                    // Si llegamos aquí, la conexión fue exitosa
                    error_log("Conexión exitosa con configuración: Host={$config['host']}, Puerto={$config['port']}, Usuario={$config['user']}");
                    break;
                    
                } catch (PDOException $e) {
                    $lastError = $e->getMessage();
                    continue;
                }
            }
            
            if ($this->connection === null) {
                throw new Exception("No se pudo conectar con ninguna configuración. Último error: " . $lastError);
            }
            
            // Verificar que la base de datos existe y crear tablas si es necesario
            $this->initializeDatabase();
            
        } catch (Exception $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    private function initializeDatabase() {
        try {
            // Crear tabla users si no existe
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                subscription_type ENUM('basic', 'standard', 'premium') DEFAULT 'basic',
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->connection->exec($sql);
            
            // Crear tabla profiles si no existe
            $sql = "CREATE TABLE IF NOT EXISTS profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                avatar VARCHAR(255) DEFAULT 'avatar1.png',
                is_kids BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $this->connection->exec($sql);
            
            // Crear usuario admin por defecto si no existe
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute(['admin@netflix.com']);
            
            if ($stmt->fetchColumn() == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->connection->prepare("INSERT INTO users (email, password, subscription_type, is_admin) VALUES (?, ?, ?, ?)");
                $stmt->execute(['admin@netflix.com', $hashedPassword, 'premium', 1]);
                
                $userId = $this->connection->lastInsertId();
                
                // Crear perfil para admin
                $stmt = $this->connection->prepare("INSERT INTO profiles (user_id, name, avatar) VALUES (?, ?, ?)");
                $stmt->execute([$userId, 'Admin', 'avatar1.png']);
            }
            
        } catch (PDOException $e) {
            error_log("Error inicializando base de datos: " . $e->getMessage());
            // No lanzar excepción aquí para permitir que la aplicación continúe
        }
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Función global para obtener la conexión
function getConnection() {
    return Database::getInstance()->getConnection();
}

// Función para probar la conexión
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        return $db->testConnection();
    } catch (Exception $e) {
        error_log("Error probando conexión: " . $e->getMessage());
        return false;
    }
}
?>
