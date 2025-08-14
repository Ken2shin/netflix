<?php
require_once 'config/config.php';
require_once 'controllers/AdminController.php';

$adminController = new AdminController();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminController->processAddContent();
} else {
    $adminController->showAddContent();
}
?>
