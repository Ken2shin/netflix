<?php
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';
require_once 'models/Profile.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $authController->selectProfile();
} else {
    // Mostrar página de selección de perfiles
    $profile = new Profile();
    $profiles = $profile->getProfilesByUser($_SESSION['user_id']);
    include 'views/select-profile.php';
}
?>
