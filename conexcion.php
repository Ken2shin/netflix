<?php
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=netflix", "root", "emanuel12*");
    echo "Conexión exitosa.";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
