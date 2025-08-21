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
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Solo crear tablas si no existen - SIN insertar datos automáticamente
            $this->createTablesIfNotExist();
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    private function createTablesIfNotExist() {
        try {
            // Verificar si la tabla users existe y obtener su estructura
            $stmt = $this->connection->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                // Crear tabla users con estructura básica
                $sql = "CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    is_admin BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $this->connection->exec($sql);
            }
            
            // Verificar si la tabla content existe
            $stmt = $this->connection->query("SHOW TABLES LIKE 'content'");
            $contentTableExists = $stmt->rowCount() > 0;
            
            if (!$contentTableExists) {
                // Crear tabla content con estructura básica
                $sql = "CREATE TABLE content (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    description TEXT,
                    type ENUM('movie', 'series') NOT NULL,
                    genre VARCHAR(100),
                    release_year INT,
                    duration INT,
                    rating VARCHAR(10) DEFAULT 'PG',
                    poster_url VARCHAR(500),
                    backdrop_url VARCHAR(500),
                    video_url VARCHAR(500),
                    is_featured BOOLEAN DEFAULT FALSE,
                    is_trending BOOLEAN DEFAULT FALSE,
                    view_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $this->connection->exec($sql);
            }
            
        } catch (PDOException $e) {
            // Si hay error creando tablas, continuar sin fallar
            error_log("Error creando tablas: " . $e->getMessage());
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
    
    // Método para verificar si una columna existe en una tabla
    public function columnExists($table, $column) {
        try {
            $stmt = $this->connection->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Método para obtener las columnas de una tabla
    public function getTableColumns($table) {
        try {
            $stmt = $this->connection->query("SHOW COLUMNS FROM `$table`");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
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

// Función para verificar si una columna existe
function columnExists($table, $column) {
    try {
        $db = Database::getInstance();
        return $db->columnExists($table, $column);
    } catch (Exception $e) {
        return false;
    }
}

// Función para obtener columnas de una tabla
function getTableColumns($table) {
    try {
        $db = Database::getInstance();
        return $db->getTableColumns($table);
    } catch (Exception $e) {
        return [];
    }
}
?>
