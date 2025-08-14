<?php
class User {
    private $conn;
    private $table_name = "users";
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
    }
    
    public function login($email, $password) {
        try {
            $query = "SELECT id, name, email, password, is_admin FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function register($name, $email, $password) {
        try {
            // Verificar si el email ya existe
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }

            // Insertar nuevo usuario
            $query = "INSERT INTO " . $this->table_name . " (name, email, password) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            if ($stmt->execute([$name, $email, $hashedPassword])) {
                return ['success' => true, 'user_id' => $this->conn->lastInsertId()];
            }
            
            return ['success' => false, 'message' => 'Error al crear usuario'];
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Find by email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function findById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Find by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $query = "SELECT id, name, email, is_admin, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
}
?>
