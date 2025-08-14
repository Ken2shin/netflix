<?php
require_once 'config/database.php';

class Season {
    private $conn;
    private $table = 'seasons';
    
    public $id;
    public $content_id;
    public $season_number;
    public $title;
    public $description;
    public $release_year;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener temporadas por serie
    public function getSeasonsByContent($content_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE content_id = :content_id 
                  ORDER BY season_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener temporada por ID
    public function getSeasonById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->content_id = $row['content_id'];
            $this->season_number = $row['season_number'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->release_year = $row['release_year'];
            $this->created_at = $row['created_at'];
            
            return $row;
        }
        
        return false;
    }
}
?>
