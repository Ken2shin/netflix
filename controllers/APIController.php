<?php
require_once 'config/api.php';
require_once 'middleware/auth.php';

class APIController {
    private $api;
    
    public function __construct() {
        $this->api = new NetflixAPI();
        
        // Si hay token en sesión, configurarlo
        if (isset($_SESSION['api_token'])) {
            $this->api->setAuthToken($_SESSION['api_token']);
        }
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email y contraseña son requeridos'];
            }
            
            // Intentar login con API externa
            $result = $this->api->login($email, $password);
            
            if ($result && isset($result['user'])) {
                // Guardar datos en sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['subscription_type'] = $result['user']['subscription_type'] ?? 'basic';
                $_SESSION['is_admin'] = $result['user']['is_admin'] ?? false;
                $_SESSION['api_token'] = $result['token'] ?? null;
                
                return ['success' => true, 'message' => 'Login exitoso'];
            } else {
                // Fallback a base de datos local si falla API
                return $this->localLogin($email, $password);
            }
        }
        
        return ['success' => false, 'message' => 'Método no permitido'];
    }
    
    private function localLogin($email, $password) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['subscription_type'] = $user['subscription_type'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                return ['success' => true, 'message' => 'Login exitoso (local)'];
            }
            
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        } catch (Exception $e) {
            error_log('Local Login Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el servidor'];
        }
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Todos los campos son requeridos'];
            }
            
            // Intentar registro con API externa
            $result = $this->api->register($username, $email, $password);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registro exitoso'];
            } else {
                // Fallback a base de datos local
                return $this->localRegister($username, $email, $password);
            }
        }
        
        return ['success' => false, 'message' => 'Método no permitido'];
    }
    
    private function localRegister($username, $email, $password) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Verificar si el email ya existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }
            
            // Crear nuevo usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, subscription_type, created_at) VALUES (?, ?, ?, 'basic', NOW())");
            
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                return ['success' => true, 'message' => 'Registro exitoso (local)'];
            }
            
            return ['success' => false, 'message' => 'Error al crear usuario'];
        } catch (Exception $e) {
            error_log('Local Register Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el servidor'];
        }
    }
    
    public function getAllMedia() {
        try {
            $media = $this->api->getAllMedia();
            
            if (empty($media)) {
                // Fallback a base de datos local
                return $this->getLocalMedia();
            }
            
            return $media;
        } catch (Exception $e) {
            error_log('Error fetching all media: ' . $e->getMessage());
            return $this->getLocalMedia();
        }
    }
    
    private function getLocalMedia() {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM content ORDER BY created_at DESC");
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay contenido local, crear contenido de ejemplo
            if (empty($results)) {
                return $this->createSampleContent();
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Local Media Error: ' . $e->getMessage());
            return $this->createSampleContent();
        }
    }
    
    private function createSampleContent() {
        return [
            [
                'id' => 1,
                'title' => 'Stranger Things',
                'description' => 'Una serie de ciencia ficción y terror sobrenatural ambientada en los años 80.',
                'type' => 'series',
                'poster_url' => '/placeholder.svg?height=300&width=200&text=Stranger+Things',
                'backdrop_url' => '/placeholder.svg?height=600&width=1200&text=Stranger+Things+Backdrop',
                'rating' => 'TV-14',
                'year' => 2016,
                'genres' => 'Ciencia ficción, Terror, Drama'
            ],
            [
                'id' => 2,
                'title' => 'The Witcher',
                'description' => 'Un cazador de monstruos lucha por encontrar su lugar en un mundo donde las personas a menudo demuestran ser más malvadas que las bestias.',
                'type' => 'series',
                'poster_url' => '/placeholder.svg?height=300&width=200&text=The+Witcher',
                'backdrop_url' => '/placeholder.svg?height=600&width=1200&text=The+Witcher+Backdrop',
                'rating' => 'TV-MA',
                'year' => 2019,
                'genres' => 'Fantasía, Aventura, Drama'
            ],
            [
                'id' => 3,
                'title' => 'Red Notice',
                'description' => 'Un agente del FBI se asocia con un ladrón de arte rival para atrapar a una ladrona que siempre está un paso adelante.',
                'type' => 'movie',
                'poster_url' => '/placeholder.svg?height=300&width=200&text=Red+Notice',
                'backdrop_url' => '/placeholder.svg?height=600&width=1200&text=Red+Notice+Backdrop',
                'rating' => 'PG-13',
                'year' => 2021,
                'duration' => 118,
                'genres' => 'Acción, Comedia, Crimen'
            ],
            [
                'id' => 4,
                'title' => 'Squid Game',
                'description' => 'Cientos de jugadores con problemas de dinero aceptan una invitación para competir en juegos infantiles por un premio tentador.',
                'type' => 'series',
                'poster_url' => '/placeholder.svg?height=300&width=200&text=Squid+Game',
                'backdrop_url' => '/placeholder.svg?height=600&width=1200&text=Squid+Game+Backdrop',
                'rating' => 'TV-MA',
                'year' => 2021,
                'genres' => 'Thriller, Drama, Acción'
            ],
            [
                'id' => 5,
                'title' => 'Extraction',
                'description' => 'Un mercenario de operaciones encubiertas se embarca en una misión para rescatar al hijo secuestrado de un señor del crimen.',
                'type' => 'movie',
                'poster_url' => '/placeholder.svg?height=300&width=200&text=Extraction',
                'backdrop_url' => '/placeholder.svg?height=600&width=1200&text=Extraction+Backdrop',
                'rating' => 'R',
                'year' => 2020,
                'duration' => 116,
                'genres' => 'Acción, Thriller'
            ]
        ];
    }
    
    public function getMediaById($mediaId) {
        try {
            $media = $this->api->getMediaById($mediaId);
            
            if (!$media) {
                return $this->getLocalMediaById($mediaId);
            }
            
            return $media;
        } catch (Exception $e) {
            error_log('Error fetching media by ID: ' . $e->getMessage());
            return $this->getLocalMediaById($mediaId);
        }
    }
    
    private function getLocalMediaById($mediaId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
            $stmt->execute([$mediaId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Buscar en contenido de ejemplo
                $sampleContent = $this->createSampleContent();
                foreach ($sampleContent as $item) {
                    if ($item['id'] == $mediaId) {
                        return $item;
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Local Media By ID Error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function searchMedia($query) {
        try {
            $results = $this->api->searchMedia($query);
            
            if (empty($results)) {
                return $this->localSearch($query);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Error searching media: ' . $e->getMessage());
            return $this->localSearch($query);
        }
    }
    
    private function localSearch($query) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM content WHERE title LIKE ? OR description LIKE ? ORDER BY title");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm, $searchTerm]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                // Buscar en contenido de ejemplo
                $sampleContent = $this->createSampleContent();
                $results = array_filter($sampleContent, function($item) use ($query) {
                    return stripos($item['title'], $query) !== false || 
                           stripos($item['description'], $query) !== false;
                });
            }
            
            return array_values($results);
        } catch (Exception $e) {
            error_log('Local Search Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWatchlist($userId) {
        try {
            $watchlist = $this->api->getWatchlist($userId);
            
            if (empty($watchlist)) {
                return $this->getLocalWatchlist($userId);
            }
            
            return $watchlist;
        } catch (Exception $e) {
            error_log('Error fetching watchlist: ' . $e->getMessage());
            return $this->getLocalWatchlist($userId);
        }
    }
    
    private function getLocalWatchlist($userId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT c.* FROM content c 
                INNER JOIN watchlist w ON c.id = w.content_id 
                WHERE w.user_id = ? 
                ORDER BY w.added_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Local Watchlist Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function addToWatchlist($mediaId, $userId) {
        try {
            $result = $this->api->addToWatchlist($mediaId, $userId);
            
            if (!$result) {
                return $this->localAddToWatchlist($mediaId, $userId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Error adding to watchlist: ' . $e->getMessage());
            return $this->localAddToWatchlist($mediaId, $userId);
        }
    }
    
    private function localAddToWatchlist($mediaId, $userId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("INSERT IGNORE INTO watchlist (user_id, content_id, added_at) VALUES (?, ?, NOW())");
            return $stmt->execute([$userId, $mediaId]);
        } catch (Exception $e) {
            error_log('Local Add Watchlist Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function removeFromWatchlist($mediaId, $userId) {
        try {
            $result = $this->api->removeFromWatchlist($mediaId, $userId);
            
            if (!$result) {
                return $this->localRemoveFromWatchlist($mediaId, $userId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Error removing from watchlist: ' . $e->getMessage());
            return $this->localRemoveFromWatchlist($mediaId, $userId);
        }
    }
    
    private function localRemoveFromWatchlist($mediaId, $userId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM watchlist WHERE user_id = ? AND content_id = ?");
            return $stmt->execute([$userId, $mediaId]);
        } catch (Exception $e) {
            error_log('Local Remove Watchlist Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getRecommendations($userId) {
        try {
            $recommendations = $this->api->getRecommendations($userId);
            
            if (empty($recommendations)) {
                return $this->getLocalRecommendations($userId);
            }
            
            return $recommendations;
        } catch (Exception $e) {
            error_log('Error fetching recommendations: ' . $e->getMessage());
            return $this->getLocalRecommendations($userId);
        }
    }
    
    private function getLocalRecommendations($userId) {
        // Retornar contenido aleatorio como recomendaciones
        $allMedia = $this->getLocalMedia();
        shuffle($allMedia);
        return array_slice($allMedia, 0, 10);
    }
    
    public function getUserHistory($userId) {
        try {
            $history = $this->api->getUserHistory($userId);
            
            if (empty($history)) {
                return $this->getLocalHistory($userId);
            }
            
            return $history;
        } catch (Exception $e) {
            error_log('Error fetching user history: ' . $e->getMessage());
            return $this->getLocalHistory($userId);
        }
    }
    
    private function getLocalHistory($userId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT c.*, vh.watched_at, vh.progress 
                FROM content c 
                INNER JOIN viewing_history vh ON c.id = vh.content_id 
                WHERE vh.user_id = ? 
                ORDER BY vh.watched_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Local History Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function startStreaming($mediaId, $userId) {
        try {
            $result = $this->api->streamMedia($mediaId, $userId);
            
            if ($result) {
                // Agregar al historial local también
                $this->addToLocalHistory($userId, $mediaId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Error starting stream: ' . $e->getMessage());
            return $this->addToLocalHistory($userId, $mediaId);
        }
    }
    
    private function addToLocalHistory($userId, $mediaId) {
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO viewing_history (user_id, content_id, watched_at, progress) 
                VALUES (?, ?, NOW(), 0) 
                ON DUPLICATE KEY UPDATE watched_at = NOW()
            ");
            
            return $stmt->execute([$userId, $mediaId]);
        } catch (Exception $e) {
            error_log('Local History Add Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        $this->api->logout();
        
        // Limpiar sesión
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout exitoso'];
    }
}
?>
