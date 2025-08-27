<?php
require_once 'config/config.php';

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: login.php');
exit();
?>
