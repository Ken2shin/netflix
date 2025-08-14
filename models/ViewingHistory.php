<?php
require_once 'config/database.php';

class ViewingHistory {
    private $conn;
    private $table = 'viewing_history';
    
    public $id;
    public $profile_id;
    public $content_id;
    public $episode_id;
    public $watch_time;
    public $total_time;
    public $completed;
    public $last_watched;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Actualizar progreso de visualización
    public function updateProgress($profile_id, $content_id, $watch_time, $total_time, $episode_id = null) {
        $completed = ($watch_time >= $total_time * 0.9) ? 1 : 0; // 90% considerado como completado
        
        // Verificar si ya existe un registro
        $check_query = "SELECT id FROM " . $this->table . " 
                       WHERE profile_id = :profile_id AND content_id = :content_id";
        
        if($episode_id) {
            $check_query .= " AND episode_id = :episode_id";
        } else {
            $check_query .= " AND episode_id IS NULL";
        }
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        
        if($episode_id) {
            $check_stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
        }
        
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            // Actualizar registro existente
            $query = "UPDATE " . $this->table . " 
                      SET watch_time = :watch_time, total_time = :total_time, 
                          completed = :completed, last_watched = NOW()
                      WHERE profile_id = :profile_id AND content_id = :content_id";
            
            if($episode_id) {
                $query .= " AND episode_id = :episode_id";
            } else {
                $query .= " AND episode_id IS NULL";
            }
        } else {
            // Crear nuevo registro
            $query = "INSERT INTO " . $this->table . " 
                      (profile_id, content_id, episode_id, watch_time, total_time, completed) 
                      VALUES (:profile_id, :content_id, :episode_id, :watch_time, :total_time, :completed)";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        $stmt->bindParam(':watch_time', $watch_time, PDO::PARAM_INT);
        $stmt->bindParam(':total_time', $total_time, PDO::PARAM_INT);
        $stmt->bindParam(':completed', $completed, PDO::PARAM_BOOL);
        
        if($episode_id) {
            $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':episode_id', null, PDO::PARAM_NULL);
        }
        
        return $stmt->execute();
    }
    
    // Obtener progreso de visualización
    public function getProgress($profile_id, $content_id, $episode_id = null) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE profile_id = :profile_id AND content_id = :content_id";
        
        if($episode_id) {
            $query .= " AND episode_id = :episode_id";
        } else {
            $query .= " AND episode_id IS NULL";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        
        if($episode_id) {
            $stmt->bindParam(':episode_id', $episode_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener historial de visualización
    public function getViewingHistory($profile_id, $limit = 20, $offset = 0) {
        $query = "SELECT vh.*, c.title, c.thumbnail, c.type, c.duration,
                         e.title as episode_title, e.episode_number,
                         s.season_number
                  FROM " . $this->table . " vh
                  INNER JOIN content c ON vh.content_id = c.id
                  LEFT JOIN episodes e ON vh.episode_id = e.id
                  LEFT JOIN seasons s ON e.season_id = s.id
                  WHERE vh.profile_id = :profile_id
                  ORDER BY vh.last_watched DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener contenido para continuar viendo
    public function getContinueWatching($profile_id, $limit = 10) {
        $query = "SELECT vh.*, c.title, c.thumbnail, c.type, c.duration,
                         e.title as episode_title, e.episode_number,
                         s.season_number
                  FROM " . $this->table . " vh
                  INNER JOIN content c ON vh.content_id = c.id
                  LEFT JOIN episodes e ON vh.episode_id = e.id
                  LEFT JOIN seasons s ON e.season_id = s.id
                  WHERE vh.profile_id = :profile_id 
                  AND vh.completed = 0 
                  AND vh.watch_time > 60
                  ORDER BY vh.last_watched DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profile_id', $profile_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
