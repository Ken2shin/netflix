<?php
require_once 'config/config.php';
require_once 'controllers/AdminController.php';

$adminController = new AdminController();
$adminController->showDashboard();
?>
