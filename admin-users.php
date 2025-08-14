<?php
require_once 'config/config.php';
require_once 'controllers/AdminController.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$adminController = new AdminController();
$adminController->showUserManagement($page);
?>
