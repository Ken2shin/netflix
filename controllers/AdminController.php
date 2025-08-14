<?php
require_once 'models/User.php';
require_once 'models/Content.php';
require_once 'models/Genre.php';
require_once 'models/Profile.php';
require_once 'models/ViewingHistory.php';
require_once 'middleware/auth.php';

class AdminController {
    
    // Dashboard principal
    public function showDashboard() {
        requireAdmin();
        
        $user = new User();
        $content = new Content();
        $profile = new Profile();
        $viewingHistory = new ViewingHistory();
        
        // Obtener estadísticas generales
        $stats = $this->getGeneralStats();
        
        // Contenido más visto
        $topContent = $this->getTopContent(10);
        
        // Usuarios recientes
        $recentUsers = $this->getRecentUsers(5);
        
        $data = [
            'stats' => $stats,
            'topContent' => $topContent,
            'recentUsers' => $recentUsers
        ];
        
        include 'views/admin/dashboard.php';
    }
    
    // Gestión de contenido
    public function showContentManagement($page = 1) {
        requireAdmin();
        
        $content = new Content();
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Obtener todo el contenido con paginación
        $contentList = $this->getAllContent($limit, $offset);
        $totalContent = $this->getTotalContentCount();
        
        $data = [
            'content' => $contentList,
            'currentPage' => $page,
            'totalPages' => ceil($totalContent / $limit),
            'totalContent' => $totalContent
        ];
        
        include 'views/admin/content-management.php';
    }
    
    // Formulario para agregar contenido
    public function showAddContent() {
        requireAdmin();
        
        $genre = new Genre();
        $genres = $genre->getAllGenres();
        
        $data = ['genres' => $genres];
        include 'views/admin/add-content.php';
    }
    
    // Procesar agregar contenido
    public function processAddContent() {
        requireAdmin();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: admin-content.php');
            exit();
        }
        
        // Validar token CSRF
        if(!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: admin-add-content.php');
            exit();
        }
        
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $type = sanitize($_POST['type']);
        $release_year = (int)$_POST['release_year'];
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : null;
        $rating = sanitize($_POST['rating']);
        $imdb_rating = (float)$_POST['imdb_rating'];
        $genres = $_POST['genres'] ?? [];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_trending = isset($_POST['is_trending']) ? 1 : 0;
        
        // Validaciones
        if(empty($title) || empty($description) || empty($type)) {
            $_SESSION['error'] = 'Por favor completa todos los campos obligatorios';
            header('Location: admin-add-content.php');
            exit();
        }
        
        // Subir archivos
        $thumbnail = $this->uploadFile($_FILES['thumbnail'], 'thumbnails');
        $banner_image = $this->uploadFile($_FILES['banner_image'], 'banners');
        $trailer_url = $this->uploadFile($_FILES['trailer'], 'trailers');
        $video_url = $this->uploadFile($_FILES['video'], 'videos');
        
        if(!$thumbnail || !$banner_image || !$video_url) {
            $_SESSION['error'] = 'Error al subir los archivos. Verifica que todos los archivos sean válidos.';
            header('Location: admin-add-content.php');
            exit();
        }
        
        // Crear contenido
        $content = new Content();
        $content->title = $title;
        $content->description = $description;
        $content->type = $type;
        $content->release_year = $release_year;
        $content->duration = $duration;
        $content->rating = $rating;
        $content->imdb_rating = $imdb_rating;
        $content->thumbnail = $thumbnail;
        $content->banner_image = $banner_image;
        $content->trailer_url = $trailer_url;
        $content->video_url = $video_url;
        $content->is_featured = $is_featured;
        $content->is_trending = $is_trending;
        
        if($this->createContent($content, $genres)) {
            $_SESSION['success'] = 'Contenido agregado exitosamente';
            header('Location: admin-content.php');
        } else {
            $_SESSION['error'] = 'Error al agregar el contenido';
            header('Location: admin-add-content.php');
        }
        exit();
    }
    
    // Gestión de usuarios
    public function showUserManagement($page = 1) {
        requireAdmin();
        
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $users = $this->getAllUsers($limit, $offset);
        $totalUsers = $this->getTotalUsersCount();
        
        $data = [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => ceil($totalUsers / $limit),
            'totalUsers' => $totalUsers
        ];
        
        include 'views/admin/user-management.php';
    }
    
    // Gestión de géneros
    public function showGenreManagement() {
        requireAdmin();
        
        $genre = new Genre();
        $genres = $genre->getGenresWithCount();
        
        $data = ['genres' => $genres];
        include 'views/admin/genre-management.php';
    }
    
    // Agregar género
    public function processAddGenre() {
        requireAdmin();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: admin-genres.php');
            exit();
        }
        
        $name = sanitize($_POST['name']);
        $slug = strtolower(str_replace(' ', '-', $name));
        
        if(empty($name)) {
            $_SESSION['error'] = 'El nombre del género es obligatorio';
            header('Location: admin-genres.php');
            exit();
        }
        
        if($this->createGenre($name, $slug)) {
            $_SESSION['success'] = 'Género agregado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al agregar el género';
        }
        
        header('Location: admin-genres.php');
        exit();
    }
    
    // Estadísticas detalladas
    public function showStatistics() {
        requireAdmin();
        
        $stats = [
            'general' => $this->getGeneralStats(),
            'content' => $this->getContentStats(),
            'users' => $this->getUserStats(),
            'viewing' => $this->getViewingStats()
        ];
        
        include 'views/admin/statistics.php';
    }
    
    // Métodos auxiliares
    private function getGeneralStats() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Total usuarios
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE subscription_status = 'active'");
        $stats['total_users'] = $stmt->fetch()['total'];
        
        // Total contenido
        $stmt = $conn->query("SELECT COUNT(*) as total FROM content");
        $stats['total_content'] = $stmt->fetch()['total'];
        
        // Total películas
        $stmt = $conn->query("SELECT COUNT(*) as total FROM content WHERE type = 'movie'");
        $stats['total_movies'] = $stmt->fetch()['total'];
        
        // Total series
        $stmt = $conn->query("SELECT COUNT(*) as total FROM content WHERE type = 'series'");
        $stats['total_series'] = $stmt->fetch()['total'];
        
        // Total perfiles
        $stmt = $conn->query("SELECT COUNT(*) as total FROM profiles");
        $stats['total_profiles'] = $stmt->fetch()['total'];
        
        // Visualizaciones hoy
        $stmt = $conn->query("SELECT COUNT(*) as total FROM viewing_history WHERE DATE(last_watched) = CURDATE()");
        $stats['views_today'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    private function getTopContent($limit) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT c.*, COUNT(vh.id) as total_views
                  FROM content c
                  LEFT JOIN viewing_history vh ON c.id = vh.content_id
                  GROUP BY c.id
                  ORDER BY total_views DESC, c.view_count DESC
                  LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentUsers($limit) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT u.*, COUNT(p.id) as profile_count
                  FROM users u
                  LEFT JOIN profiles p ON u.id = p.user_id
                  WHERE u.subscription_status = 'active'
                  GROUP BY u.id
                  ORDER BY u.created_at DESC
                  LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAllContent($limit, $offset) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres,
                         COUNT(vh.id) as total_views
                  FROM content c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  LEFT JOIN viewing_history vh ON c.id = vh.content_id
                  GROUP BY c.id
                  ORDER BY c.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTotalContentCount() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM content");
        return $stmt->fetch()['total'];
    }
    
    private function getAllUsers($limit, $offset) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT u.*, COUNT(p.id) as profile_count,
                         COUNT(vh.id) as total_views
                  FROM users u
                  LEFT JOIN profiles p ON u.id = p.user_id
                  LEFT JOIN viewing_history vh ON p.id = vh.profile_id
                  GROUP BY u.id
                  ORDER BY u.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTotalUsersCount() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        return $stmt->fetch()['total'];
    }
    
    private function uploadFile($file, $folder) {
        if(!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $uploadDir = "uploads/{$folder}/";
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if(move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $fileName;
        }
        
        return false;
    }
    
    private function createContent($content, $genres) {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Insertar contenido
            $query = "INSERT INTO content (title, description, type, release_year, duration, rating, 
                                         imdb_rating, thumbnail, banner_image, trailer_url, video_url, 
                                         is_featured, is_trending) 
                      VALUES (:title, :description, :type, :release_year, :duration, :rating, 
                              :imdb_rating, :thumbnail, :banner_image, :trailer_url, :video_url, 
                              :is_featured, :is_trending)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':title', $content->title);
            $stmt->bindParam(':description', $content->description);
            $stmt->bindParam(':type', $content->type);
            $stmt->bindParam(':release_year', $content->release_year);
            $stmt->bindParam(':duration', $content->duration);
            $stmt->bindParam(':rating', $content->rating);
            $stmt->bindParam(':imdb_rating', $content->imdb_rating);
            $stmt->bindParam(':thumbnail', $content->thumbnail);
            $stmt->bindParam(':banner_image', $content->banner_image);
            $stmt->bindParam(':trailer_url', $content->trailer_url);
            $stmt->bindParam(':video_url', $content->video_url);
            $stmt->bindParam(':is_featured', $content->is_featured);
            $stmt->bindParam(':is_trending', $content->is_trending);
            
            $stmt->execute();
            $contentId = $conn->lastInsertId();
            
            // Insertar géneros
            if(!empty($genres)) {
                $genreQuery = "INSERT INTO content_genres (content_id, genre_id) VALUES (:content_id, :genre_id)";
                $genreStmt = $conn->prepare($genreQuery);
                
                foreach($genres as $genreId) {
                    $genreStmt->bindParam(':content_id', $contentId);
                    $genreStmt->bindParam(':genre_id', $genreId);
                    $genreStmt->execute();
                }
            }
            
            $conn->commit();
            return true;
            
        } catch(Exception $e) {
            $conn->rollback();
            return false;
        }
    }
    
    private function createGenre($name, $slug) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO genres (name, slug) VALUES (:name, :slug)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        
        return $stmt->execute();
    }
    
    private function getContentStats() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Contenido por tipo
        $stmt = $conn->query("SELECT type, COUNT(*) as count FROM content GROUP BY type");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contenido por año
        $stmt = $conn->query("SELECT release_year, COUNT(*) as count FROM content 
                             WHERE release_year IS NOT NULL 
                             GROUP BY release_year 
                             ORDER BY release_year DESC 
                             LIMIT 10");
        $stats['by_year'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contenido por rating
        $stmt = $conn->query("SELECT rating, COUNT(*) as count FROM content GROUP BY rating");
        $stats['by_rating'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    private function getUserStats() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Usuarios por tipo de suscripción
        $stmt = $conn->query("SELECT subscription_type, COUNT(*) as count FROM users 
                             WHERE subscription_status = 'active' 
                             GROUP BY subscription_type");
        $stats['by_subscription'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Registros por mes
        $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                             FROM users 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                             GROUP BY month 
                             ORDER BY month DESC");
        $stats['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    private function getViewingStats() {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stats = [];
        
        // Visualizaciones por día (últimos 7 días)
        $stmt = $conn->query("SELECT DATE(last_watched) as date, COUNT(*) as count 
                             FROM viewing_history 
                             WHERE last_watched >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                             GROUP BY date 
                             ORDER BY date DESC");
        $stats['by_day'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contenido más visto
        $stmt = $conn->query("SELECT c.title, c.type, COUNT(vh.id) as views 
                             FROM content c
                             INNER JOIN viewing_history vh ON c.id = vh.content_id
                             GROUP BY c.id
                             ORDER BY views DESC
                             LIMIT 10");
        $stats['most_watched'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?>
