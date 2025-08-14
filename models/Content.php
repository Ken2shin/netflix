<?php
require_once 'config/database.php';

class Content {
    private $conn;
    private $table = 'content';
    
    public $id;
    public $title;
    public $description;
    public $type;
    public $release_year;
    public $duration;
    public $rating;
    public $imdb_rating;
    public $thumbnail;
    public $banner_image;
    public $trailer_url;
    public $video_url;
    public $is_featured;
    public $is_trending;
    public $view_count;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener contenido destacado
    public function getFeaturedContent($limit = 10) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.is_featured = 1
                  GROUP BY c.id
                  ORDER BY c.view_count DESC, c.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido en tendencia
    public function getTrendingContent($limit = 10) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.is_trending = 1
                  GROUP BY c.id
                  ORDER BY c.view_count DESC, c.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido por tipo (movie/series)
    public function getContentByType($type, $limit = 20, $offset = 0) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.type = :type
                  GROUP BY c.id
                  ORDER BY c.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido por género
    public function getContentByGenre($genre_id, $limit = 20, $offset = 0) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  INNER JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN content_genres cg2 ON c.id = cg2.content_id
                  LEFT JOIN genres g ON cg2.genre_id = g.id
                  WHERE cg.genre_id = :genre_id
                  GROUP BY c.id
                  ORDER BY c.view_count DESC, c.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido por ID
    public function getContentById($id) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres,
                         GROUP_CONCAT(g.id SEPARATOR ',') as genre_ids
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.id = :id
                  GROUP BY c.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->type = $row['type'];
            $this->release_year = $row['release_year'];
            $this->duration = $row['duration'];
            $this->rating = $row['rating'];
            $this->imdb_rating = $row['imdb_rating'];
            $this->thumbnail = $row['thumbnail'];
            $this->banner_image = $row['banner_image'];
            $this->trailer_url = $row['trailer_url'];
            $this->video_url = $row['video_url'];
            $this->is_featured = $row['is_featured'];
            $this->is_trending = $row['is_trending'];
            $this->view_count = $row['view_count'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return $row;
        }
        
        return false;
    }
    
    // Buscar contenido
    public function searchContent($search_term, $limit = 20, $offset = 0) {
        $search_term = '%' . $search_term . '%';
        
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.title LIKE :search_term OR c.description LIKE :search_term
                  GROUP BY c.id
                  ORDER BY c.view_count DESC, c.title ASC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search_term', $search_term);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido similar
    public function getSimilarContent($content_id, $limit = 10) {
        $query = "SELECT DISTINCT c2.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c1
                  INNER JOIN content_genres cg1 ON c1.id = cg1.content_id
                  INNER JOIN content_genres cg2 ON cg1.genre_id = cg2.genre_id
                  INNER JOIN " . $this->table . " c2 ON cg2.content_id = c2.id
                  LEFT JOIN content_genres cg3 ON c2.id = cg3.content_id
                  LEFT JOIN genres g ON cg3.genre_id = g.id
                  WHERE c1.id = :content_id AND c2.id != :content_id
                  GROUP BY c2.id
                  ORDER BY c2.view_count DESC, c2.imdb_rating DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Incrementar contador de visualizaciones
    public function incrementViewCount($content_id) {
        $query = "UPDATE " . $this->table . " SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $content_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Obtener contenido recién agregado
    public function getRecentlyAdded($limit = 20) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  GROUP BY c.id
                  ORDER BY c.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido mejor calificado
    public function getTopRated($limit = 20) {
        $query = "SELECT c.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " c
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE c.imdb_rating >= 7.0
                  GROUP BY c.id
                  ORDER BY c.imdb_rating DESC, c.view_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
