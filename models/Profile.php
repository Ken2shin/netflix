<?php
class Profile {
    private $conn;
    private $table_name = "profiles";
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
    }
    
    public function create($user_id, $name, $avatar = 'avatar1.png', $is_kids = false) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (user_id, name, avatar, is_kids) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt->execute([$user_id, $name, $avatar, $is_kids])) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Profile creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUserId($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get profiles error: " . $e->getMessage());
            return [];
        }
    }
    
    public function findById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Find profile error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $name, $avatar = null, $is_kids = null) {
        try {
            $fields = ["name = ?"];
            $params = [$name];
            
            if ($avatar !== null) {
                $fields[] = "avatar = ?";
                $params[] = $avatar;
            }
            
            if ($is_kids !== null) {
                $fields[] = "is_kids = ?";
                $params[] = $is_kids;
            }
            
            $params[] = $id;
            
            $query = "UPDATE " . $this->table_name . " SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Profile delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function countByUserId($user_id) {
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Count profiles error: " . $e->getMessage());
            return 0;
        }
    }
}
?>
