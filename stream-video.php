<?php
require_once 'includes/video-handler.php';
require_once 'config/config.php';

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit('No file specified');
}

$file_path = $_GET['file'];

// Security check - ensure file is in uploads directory
if (strpos($file_path, 'uploads/videos/') !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Additional security - prevent directory traversal
if (strpos($file_path, '..') !== false) {
    http_response_code(403);
    exit('Access denied');
}

$video_handler = new VideoHandler();
$video_handler->streamVideo($file_path);
?>
