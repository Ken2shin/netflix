<?php
require_once __DIR__ . '/../config/database.php';

class Profile {
    private $db;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database->getConnection();
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }
    
    public function findByUserId($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at ASC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding profiles by user ID: " . $e->getMessage());
            return [];
        }
    }
    
    public function findByUserIdAndName($user_id, $name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profiles WHERE user_id = ? AND name = ?");
            $stmt->execute([$user_id, $name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding profile by user ID and name: " . $e->getMessage());
            return false;
        }
    }
    
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profiles WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding profile by ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($user_id, $name, $avatar = 'avatar1.png', $is_kids = 0) {
        try {
            $database = Database::getInstance();
            if (!$database->columnExists('profiles', 'is_kids')) {
                // Si no existe la columna, agregarla
                $this->db->exec("ALTER TABLE profiles ADD COLUMN is_kids BOOLEAN DEFAULT FALSE");
            }
            
            $stmt = $this->db->prepare("INSERT INTO profiles (user_id, name, avatar, is_kids) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$user_id, $name, $avatar, $is_kids]);
        } catch (PDOException $e) {
            error_log("Error creating profile: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $name, $avatar = null, $is_kids = null) {
        try {
            $sql = "UPDATE profiles SET name = ?";
            $params = [$name];
            
            if ($avatar !== null) {
                $sql .= ", avatar = ?";
                $params[] = $avatar;
            }
            
            if ($is_kids !== null) {
                $sql .= ", is_kids = ?";
                $params[] = $is_kids;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM profiles WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting profile: " . $e->getMessage());
            return false;
        }
    }
    
    public function countByUserId($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting profiles: " . $e->getMessage());
            return 0;
        }
    }
}
?>
