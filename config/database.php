<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Error de conexión a la base de datos");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
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

// Función helper para obtener conexión
function getConnection() {
    return Database::getInstance()->getConnection();
}

function getTableColumns($table) {
    return Database::getInstance()->getTableColumns($table);
}

// Crear tablas si no existen
function createTables() {
    try {
        $conn = getConnection();
        
        // Tabla users
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            subscription_plan ENUM('basico', 'normal', 'premium') DEFAULT 'basico',
            subscription_status ENUM('active', 'inactive', 'cancelled') DEFAULT 'inactive',
            subscription_start_date TIMESTAMP NULL,
            subscription_end_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Tabla profiles
        $conn->exec("CREATE TABLE IF NOT EXISTS profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT 'avatar1.png',
            is_kids BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $database = Database::getInstance();
        if (!$database->columnExists('profiles', 'is_kids')) {
            $conn->exec("ALTER TABLE profiles ADD COLUMN is_kids BOOLEAN DEFAULT FALSE");
        }

        // Tabla content
        $conn->exec("CREATE TABLE IF NOT EXISTS content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type ENUM('movie', 'series') NOT NULL,
            genre VARCHAR(255),
            release_year INT,
            duration INT,
            rating VARCHAR(10),
            poster_url VARCHAR(255),
            backdrop_url VARCHAR(255),
            thumbnail VARCHAR(255),
            video_url VARCHAR(255),
            video_platform VARCHAR(50) DEFAULT 'direct',
            required_plan ENUM('basico', 'normal', 'premium') DEFAULT 'basico',
            imdb_id VARCHAR(20) UNIQUE,
            imdb_rating DECIMAL(3,1),
            metascore INT,
            plot TEXT,
            director VARCHAR(255),
            writer TEXT,
            actors TEXT,
            awards TEXT,
            box_office VARCHAR(100),
            country VARCHAR(100),
            language VARCHAR(100),
            production VARCHAR(255),
            data_source ENUM('manual', 'omdb') DEFAULT 'manual',
            last_omdb_update TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_imdb_id (imdb_id),
            INDEX idx_data_source (data_source),
            INDEX idx_type (type),
            INDEX idx_genre (genre),
            INDEX idx_release_year (release_year)
        )");

        if (!$database->columnExists('content', 'imdb_id')) {
            $conn->exec("ALTER TABLE content 
                ADD COLUMN imdb_id VARCHAR(20) UNIQUE,
                ADD COLUMN imdb_rating DECIMAL(3,1),
                ADD COLUMN metascore INT,
                ADD COLUMN plot TEXT,
                ADD COLUMN director VARCHAR(255),
                ADD COLUMN writer TEXT,
                ADD COLUMN actors TEXT,
                ADD COLUMN awards TEXT,
                ADD COLUMN box_office VARCHAR(100),
                ADD COLUMN country VARCHAR(100),
                ADD COLUMN language VARCHAR(100),
                ADD COLUMN production VARCHAR(255),
                ADD COLUMN data_source ENUM('manual', 'omdb') DEFAULT 'manual',
                ADD COLUMN last_omdb_update TIMESTAMP NULL");
            
            // Add indexes
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_content_imdb_id ON content(imdb_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_content_data_source ON content(data_source)");
        }

        $conn->exec("CREATE TABLE IF NOT EXISTS omdb_search_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            search_query VARCHAR(255) NOT NULL,
            search_type VARCHAR(20) DEFAULT 'title',
            search_year INT NULL,
            response_data JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
            INDEX idx_search_query (search_query),
            INDEX idx_expires_at (expires_at)
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS omdb_failed_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_type VARCHAR(50) NOT NULL,
            request_data VARCHAR(255) NOT NULL,
            error_message TEXT,
            failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            retry_count INT DEFAULT 0,
            INDEX idx_request_data (request_data),
            INDEX idx_failed_at (failed_at)
        )");

        // Tabla subscription_plans
        $conn->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            features TEXT,
            max_profiles INT DEFAULT 1,
            max_devices INT DEFAULT 1,
            video_quality VARCHAR(20) DEFAULT 'SD',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_name VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            transaction_id VARCHAR(255),
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS video_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            upload_id VARCHAR(255) UNIQUE NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_size BIGINT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_data LONGBLOB NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            checksum VARCHAR(64),
            INDEX idx_upload_id (upload_id),
            INDEX idx_filename (filename)
        )");

        $conn->exec("CREATE TABLE IF NOT EXISTS video_chunks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            upload_id VARCHAR(255) NOT NULL,
            chunk_index INT NOT NULL,
            chunk_data MEDIUMBLOB NOT NULL,
            chunk_size INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_chunk (upload_id, chunk_index),
            INDEX idx_upload_id (upload_id)
        )");

        $stmt = $conn->prepare("SELECT COUNT(*) FROM subscription_plans");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $conn->exec("INSERT INTO subscription_plans (name, display_name, price, features, max_profiles, max_devices, video_quality) VALUES 
                ('basico', 'Plan Básico', 9.99, 'Acceso a contenido básico,1 perfil,1 dispositivo,Calidad SD', 1, 1, 'SD'),
                ('normal', 'Plan Normal', 15.99, 'Acceso a todo el contenido,3 perfiles,2 dispositivos,Calidad HD', 3, 2, 'HD'),
                ('premium', 'Plan Premium', 19.99, 'Acceso completo,5 perfiles,4 dispositivos,Calidad 4K,Descargas offline', 5, 4, '4K')");
        }

        // Insertar usuarios demo si no existen
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $conn->exec("INSERT INTO users (email, password, name, is_admin, subscription_plan, subscription_status) VALUES 
                ('admin@netflix.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrador', TRUE, 'premium', 'active'),
                ('user@netflix.com', '" . password_hash('user123', PASSWORD_DEFAULT) . "', 'Usuario Demo', FALSE, 'basico', 'active')");
        }

        // Insertar contenido demo si no existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM content");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $conn->exec("INSERT INTO content (title, description, type, genre, release_year, duration, rating, video_url, required_plan, imdb_id, imdb_rating, director, actors, data_source) VALUES 
                ('The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 'movie', 'Drama', 1994, 142, 'R', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'basico', 'tt0111161', 9.3, 'Frank Darabont', 'Tim Robbins, Morgan Freeman', 'omdb'),
                ('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.', 'movie', 'Crime, Drama', 1972, 175, 'R', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 'normal', 'tt0068646', 9.2, 'Francis Ford Coppola', 'Marlon Brando, Al Pacino', 'omdb'),
                ('Breaking Bad', 'A high school chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine.', 'series', 'Crime, Drama, Thriller', 2008, 49, 'TV-MA', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 'premium', 'tt0903747', 9.5, 'Vince Gilligan', 'Bryan Cranston, Aaron Paul', 'omdb')");
        }

    } catch (Exception $e) {
        error_log("Error creating tables: " . $e->getMessage());
    }
}

// Crear tablas al incluir este archivo
createTables();
?>
