<?php
require_once '../config/config.php';
require_once '../controllers/PlayerController.php';

$playerController = new PlayerController();
$playerController->updateProgress();
?>
