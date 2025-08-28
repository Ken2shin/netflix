<?php
/**
 * Robust Image Display Handler
 * Forces movie poster images to display across all modules
 */

class ImageHandler {
    private static $uploadDir = 'uploads/posters/';
    private static $placeholderUrl = '/placeholder.svg?height=300&width=200&text=';
    
    /**
     * Force display of movie poster with multiple fallback strategies
     */
    public static function forceDisplayPoster($contentData, $size = 'medium') {
        error_log("[ImageHandler] Processing poster for content ID: " . ($contentData['id'] ?? 'unknown'));
        
        $imageUrl = self::resolveImagePath($contentData);
        
        if ($imageUrl) {
            error_log("[ImageHandler] Found image: " . $imageUrl);
            return $imageUrl;
        }
        
        // Force fallback strategies
        error_log("[ImageHandler] No image found, using fallback for: " . ($contentData['title'] ?? 'unknown'));
        return self::generateFallbackImage($contentData, $size);
    }
    
    /**
     * Resolve image path with multiple strategies
     */
    private static function resolveImagePath($contentData) {
        $possiblePaths = self::generatePossiblePaths($contentData);
        
        error_log("[ImageHandler] Checking paths: " . implode(', ', array_slice($possiblePaths, 0, 5)));
        
        foreach ($possiblePaths as $path) {
            if (self::validateImagePath($path)) {
                error_log("[ImageHandler] Valid path found: " . $path);
                return $path;
            }
        }
        
        // Try to download and save OMDB poster if available
        if (!empty($contentData['imdb_id'])) {
            error_log("[ImageHandler] Attempting OMDB download for IMDB ID: " . $contentData['imdb_id']);
            return self::forceDownloadOMDBPoster($contentData);
        }
        
        return null;
    }
    
    /**
     * Generate all possible image paths to check
     */
    private static function generatePossiblePaths($contentData) {
        $paths = [];
        $id = $contentData['id'] ?? 0;
        $title = $contentData['title'] ?? 'movie';
        
        // Check database fields
        if (!empty($contentData['poster_url'])) {
            $paths[] = $contentData['poster_url'];
        }
        if (!empty($contentData['thumbnail'])) {
            $paths[] = $contentData['thumbnail'];
        }
        if (!empty($contentData['backdrop_url'])) {
            $paths[] = $contentData['backdrop_url'];
        }
        
        // Check standard upload paths
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        foreach ($extensions as $ext) {
            $paths[] = self::$uploadDir . "poster_{$id}.{$ext}";
            $paths[] = self::$uploadDir . "omdb_poster_{$id}_{$ext}";
            $paths[] = self::$uploadDir . sanitize_filename($title) . ".{$ext}";
            $paths[] = self::$uploadDir . "movie_{$id}_poster.{$ext}";
        }
        
        // Check OMDB cache paths
        if (!empty($contentData['imdb_id'])) {
            foreach ($extensions as $ext) {
                $paths[] = self::$uploadDir . "omdb_{$contentData['imdb_id']}.{$ext}";
            }
        }
        
        return array_unique($paths);
    }
    
    /**
     * Validate if image path exists and is accessible
     */
    private static function validateImagePath($path) {
        if (empty($path)) return false;
        
        // Handle external URLs
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return self::validateExternalImage($path);
        }
        
        // Handle local files
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
        if (file_exists($fullPath) && is_readable($fullPath)) {
            // Verify it's actually an image
            $imageInfo = @getimagesize($fullPath);
            return $imageInfo !== false;
        }
        
        return false;
    }
    
    /**
     * Validate external image URL
     */
    private static function validateExternalImage($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        return $httpCode === 200 && strpos($contentType, 'image/') === 0;
    }
    
    /**
     * Force download OMDB poster if not exists locally
     */
    private static function forceDownloadOMDBPoster($contentData) {
        if (empty($contentData['imdb_id'])) return null;
        
        try {
            require_once __DIR__ . '/../services/OMDBService.php';
            $omdb = new OMDBService();
            
            // Get movie details from OMDB
            $omdbData = $omdb->getByImdbId($contentData['imdb_id']);
            
            if (isset($omdbData['Poster']) && $omdbData['Poster'] !== 'N/A') {
                $posterPath = $omdb->downloadPoster($omdbData['Poster'], $contentData['id']);
                
                if ($posterPath) {
                    // Update database with new poster path
                    self::updatePosterInDatabase($contentData['id'], $posterPath);
                    return $posterPath;
                }
            }
        } catch (Exception $e) {
            error_log("Force OMDB download failed: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Update poster URL in database
     */
    private static function updatePosterInDatabase($contentId, $posterPath) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $conn = getConnection();
            $stmt = $conn->prepare("UPDATE content SET poster_url = ?, thumbnail = ? WHERE id = ?");
            $result = $stmt->execute([$posterPath, $posterPath, $contentId]);
            error_log("[ImageHandler] Database updated for content ID {$contentId}: " . ($result ? 'success' : 'failed'));
        } catch (Exception $e) {
            error_log("Failed to update poster in database: " . $e->getMessage());
        }
    }
    
    /**
     * Generate fallback image with movie title
     */
    private static function generateFallbackImage($contentData, $size) {
        $title = $contentData['title'] ?? 'Movie';
        $year = $contentData['release_year'] ?? '';
        $genre = $contentData['genre'] ?? '';
        
        $dimensions = self::getSizeDimensions($size);
        $text = urlencode($title . ($year ? " ({$year})" : ''));
        
        return "/placeholder.svg?height={$dimensions['height']}&width={$dimensions['width']}&text={$text}";
    }
    
    /**
     * Get dimensions for different sizes
     */
    private static function getSizeDimensions($size) {
        switch ($size) {
            case 'small':
                return ['width' => 150, 'height' => 225];
            case 'large':
                return ['width' => 400, 'height' => 600];
            case 'medium':
            default:
                return ['width' => 200, 'height' => 300];
        }
    }
    
    /**
     * Ensure upload directory exists with proper permissions
     */
    public static function ensureUploadDirectory() {
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
            
            // Create .htaccess for security
            $htaccess = self::$uploadDir . '.htaccess';
            if (!file_exists($htaccess)) {
                $content = "Options -ExecCGI\n";
                $content .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
                $content .= "Options -Indexes\n";
                file_put_contents($htaccess, $content);
            }
        }
    }
    
    /**
     * Standardize all poster URLs in database
     */
    public static function standardizePosterUrls() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $conn = getConnection();
            
            // Get all content with missing or inconsistent poster data
            $stmt = $conn->query("SELECT id, title, poster_url, thumbnail, imdb_id FROM content");
            $content = $stmt->fetchAll();
            
            foreach ($content as $item) {
                $resolvedPath = self::resolveImagePath($item);
                
                if ($resolvedPath && $resolvedPath !== $item['poster_url']) {
                    // Update both poster_url and thumbnail for consistency
                    $updateStmt = $conn->prepare("UPDATE content SET poster_url = ?, thumbnail = ? WHERE id = ?");
                    $updateStmt->execute([$resolvedPath, $resolvedPath, $item['id']]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to standardize poster URLs: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to sanitize filename
 */
function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
    return substr($filename, 0, 100);
}

/**
 * Global function to force display poster (for easy use in templates)
 */
function force_display_poster($contentData, $size = 'medium') {
    return ImageHandler::forceDisplayPoster($contentData, $size);
}

// Initialize upload directory
ImageHandler::ensureUploadDirectory();
?>
