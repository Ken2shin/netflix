<?php
require_once 'config/database.php';

class Genre {
    private $conn;
    private $table = 'genres';
    
    public $id;
    public $name;
    public $slug;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener todos los géneros
    public function getAllGenres() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener género por ID
    public function getGenreById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->slug = $row['slug'];
            $this->created_at = $row['created_at'];
            
            return $row;
        }
        
        return false;
    }
    
    // Obtener género por slug
    public function getGenreBySlug($slug) {
        $query = "SELECT * FROM " . $this->table . " WHERE slug = :slug";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->slug = $row['slug'];
            $this->created_at = $row['created_at'];
            
            return $row;
        }
        
        return false;
    }
    
    // Obtener géneros con conteo de contenido
    public function getGenresWithCount() {
        $query = "SELECT g.*, COUNT(cg.content_id) as content_count
                  FROM " . $this->table . " g
                  LEFT JOIN content_genres cg ON g.id = cg.genre_id
                  GROUP BY g.id
                  ORDER BY content_count DESC, g.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
