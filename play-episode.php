<?php
require_once 'config/config.php';
require_once 'controllers/PlayerController.php';

$episode_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($episode_id <= 0) {
    header('Location: index.php');
    exit();
}

$playerController = new PlayerController();
$playerController->playEpisode($episode_id);
?>
