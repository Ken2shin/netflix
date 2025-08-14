<?php
require_once 'config/database.php';

class Watchlist {
    private $conn;
    private $table = 'watchlist';
    
    public $id;
    public $profile_id;
    public $content_id;
    public $added_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Agregar a mi lista
    public function addToWatchlist($profile_id, $content_id) {
        // Verificar si ya está en la lista
        if($this->isInWatchlist($profile_id, $content_id)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . " (profile_id, content_id) VALUES (:profile_id, :content_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Remover de mi lista
    public function removeFromWatchlist($profile_id, $content_id) {
        $query = "DELETE FROM " . $this->table . " WHERE profile_id = :profile_id AND content_id = :content_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Verificar si está en mi lista
    public function isInWatchlist($profile_id, $content_id) {
        $query = "SELECT id FROM " . $this->table . " WHERE profile_id = :profile_id AND content_id = :content_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Obtener mi lista
    public function getWatchlist($profile_id, $limit = 50, $offset = 0) {
        $query = "SELECT c.*, w.added_at, GROUP_CONCAT(g.name SEPARATOR ', ') as genres
                  FROM " . $this->table . " w
                  INNER JOIN content c ON w.content_id = c.id
                  LEFT JOIN content_genres cg ON c.id = cg.content_id
                  LEFT JOIN genres g ON cg.genre_id = g.id
                  WHERE w.profile_id = :profile_id
                  GROUP BY c.id
                  ORDER BY w.added_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Contar elementos en mi lista
    public function countWatchlist($profile_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE profile_id = :profile_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
