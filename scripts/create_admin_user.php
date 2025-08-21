<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = getConnection();
    
    // Verificar si ya existe el usuario admin
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@netflix.com'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo "El usuario administrador ya existe.\n";
        echo "Email: admin@netflix.com\n";
        echo "Contraseña: password\n";
        exit;
    }
    
    // Crear usuario administrador
    $adminPassword = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Administrador', 'admin@netflix.com', $adminPassword, 1]);
    
    $adminId = $db->lastInsertId();
    
    // Crear perfil para el admin
    $stmt = $db->prepare("INSERT INTO profiles (user_id, name, avatar) VALUES (?, ?, ?)");
    $stmt->execute([$adminId, 'Admin', 'avatar1.png']);
    
    echo "Usuario administrador creado exitosamente!\n";
    echo "Email: admin@netflix.com\n";
    echo "Contraseña: password\n";
    echo "ID: $adminId\n";
    
} catch (Exception $e) {
    echo "Error creando usuario administrador: " . $e->getMessage() . "\n";
}
?>
