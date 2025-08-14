<?php
require_once 'config/database.php';

class Episode {
    private $conn;
    private $table = 'episodes';
    
    public $id;
    public $season_id;
    public $episode_number;
    public $title;
    public $description;
    public $duration;
    public $video_url;
    public $thumbnail;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener episodios por temporada
    public function getEpisodesBySeason($season_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE season_id = :season_id 
                  ORDER BY episode_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener episodio por ID
    public function getEpisodeById($id) {
        $query = "SELECT e.*, s.content_id, s.season_number, c.title as series_title
                  FROM " . $this->table . " e
                  INNER JOIN seasons s ON e.season_id = s.id
                  INNER JOIN content c ON s.content_id = c.id
                  WHERE e.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->season_id = $row['season_id'];
            $this->episode_number = $row['episode_number'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->duration = $row['duration'];
            $this->video_url = $row['video_url'];
            $this->thumbnail = $row['thumbnail'];
            $this->created_at = $row['created_at'];
            
            return $row;
        }
        
        return false;
    }
    
    // Obtener siguiente episodio
    public function getNextEpisode($current_episode_id) {
        $query = "SELECT e2.*, s.content_id, s.season_number, c.title as series_title
                  FROM " . $this->table . " e1
                  INNER JOIN seasons s1 ON e1.season_id = s1.id
                  INNER JOIN seasons s ON s.content_id = s1.content_id
                  INNER JOIN " . $this->table . " e2 ON s.id = e2.season_id
                  INNER JOIN content c ON s.content_id = c.id
                  WHERE e1.id = :current_episode_id
                  AND (
                    (s.season_number = s1.season_number AND e2.episode_number > e1.episode_number)
                    OR (s.season_number > s1.season_number)
                  )
                  ORDER BY s.season_number ASC, e2.episode_number ASC
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':current_episode_id', $current_episode_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
