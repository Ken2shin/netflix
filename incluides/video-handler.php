<?php
require_once 'config/config.php';

class VideoHandler {
    private $upload_dir = 'uploads/videos/';
    private $allowed_formats = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'm4v', 'mkv'];
    
    public function __construct() {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function processVideoUrl($video_url, $platform) {
        switch ($platform) {
            case 'uploaded':
                return $this->getLocalVideoUrl($video_url);
            case 'youtube':
                return $this->processYouTubeUrl($video_url);
            case 'vimeo':
                return $this->processVimeoUrl($video_url);
            case 'direct':
                return $video_url;
            default:
                return $video_url;
        }
    }
    
    private function getLocalVideoUrl($file_path) {
        if (file_exists($file_path)) {
            return '/' . $file_path;
        }
        return null;
    }
    
    private function processYouTubeUrl($url) {
        // Extract video ID from YouTube URL
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches);
        if (isset($matches[1])) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }
        return $url;
    }
    
    private function processVimeoUrl($url) {
        // Extract video ID from Vimeo URL
        preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
        if (isset($matches[1])) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }
        return $url;
    }
    
    public function getVideoMimeType($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'm4v' => 'video/x-m4v',
            'mkv' => 'video/x-matroska'
        ];
        
        return $mime_types[$extension] ?? 'video/mp4';
    }
    
    public function streamVideo($file_path) {
        if (!file_exists($file_path)) {
            http_response_code(404);
            exit('Video not found');
        }
        
        $file_size = filesize($file_path);
        $mime_type = $this->getVideoMimeType($file_path);
        
        header("Content-Type: {$mime_type}");
        header("Accept-Ranges: bytes");
        
        // Handle range requests for video streaming
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            $ranges = explode('=', $range);
            $offsets = explode('-', $ranges[1]);
            $offset = intval($offsets[0]);
            $length = intval($offsets[1]) - $offset;
            
            if (!$length) {
                $length = $file_size - $offset;
            }
            
            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes {$offset}-" . ($offset + $length - 1) . "/{$file_size}");
            header("Content-Length: {$length}");
            
            $file = fopen($file_path, 'r');
            fseek($file, $offset);
            echo fread($file, $length);
            fclose($file);
        } else {
            header("Content-Length: {$file_size}");
            readfile($file_path);
        }
        exit;
    }
}
?>
