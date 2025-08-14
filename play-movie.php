<?php
require_once 'config/config.php';
require_once 'controllers/PlayerController.php';

$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($content_id <= 0) {
    header('Location: index.php');
    exit();
}

$playerController = new PlayerController();
$playerController->playMovie($content_id);
?>
