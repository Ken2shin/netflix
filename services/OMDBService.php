<?php
class OMDBService {
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = '905c5ec3';
        $this->baseUrl = 'http://www.omdbapi.com/'; // Changed back to HTTP as specified in requirements
    }
    
    /**
     * Search movies by title
     */
    public function searchByTitle($title, $year = null, $type = null) {
        if (!$this->checkConnectivity()) {
            http_response_code(503);
            return ['Response' => 'False', 'Error' => 'Sin conexión a la API OMDB, verifica tu internet'];
        }
        
        $params = [
            's' => $title,
            'apikey' => $this->apiKey
        ];
        
        if ($year) {
            $params['y'] = $year;
        }
        
        if ($type) {
            $params['type'] = $type; // movie, series, episode
        }
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get movie details by IMDB ID
     */
    public function getByImdbId($imdbId, $plot = 'short') {
        if (!$this->checkConnectivity()) {
            http_response_code(503);
            return ['Response' => 'False', 'Error' => 'Sin conexión a la API OMDB, verifica tu internet'];
        }
        
        $params = [
            'i' => $imdbId,
            'apikey' => $this->apiKey,
            'plot' => $plot // short, full
        ];
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get movie details by title
     */
    public function getByTitle($title, $year = null, $type = null, $plot = 'short') {
        if (!$this->checkConnectivity()) {
            http_response_code(503);
            return ['Response' => 'False', 'Error' => 'Sin conexión a la API OMDB, verifica tu internet'];
        }
        
        $params = [
            't' => $title,
            'apikey' => $this->apiKey,
            'plot' => $plot
        ];
        
        if ($year) {
            $params['y'] = $year;
        }
        
        if ($type) {
            $params['type'] = $type;
        }
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get movie details formatted for our database
     */
    public function getMovieDetails($imdbId) {
        $omdbData = $this->getByImdbId($imdbId, 'full');
        
        if (!isset($omdbData['Response']) || $omdbData['Response'] === 'False') {
            return null;
        }
        
        return $this->convertToLocalFormat($omdbData);
    }
    
    /**
     * Make HTTP request to OMDB API
     */
    private function makeRequest($params) {
        $url = $this->baseUrl . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10, // Explicit 10 second timeout as required
            CURLOPT_CONNECTTIMEOUT => 5, // 5 second connection timeout
            CURLOPT_USERAGENT => 'StreamFlix-Admin/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Cache-Control: no-cache'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            error_log("OMDB API Request failed: $url - Error: $error (Code: $errno)");
            
            if ($errno === CURLE_OPERATION_TIMEDOUT || $errno === CURLE_COULDNT_CONNECT) {
                http_response_code(503);
                return ['Response' => 'False', 'Error' => 'Timeout de conexión a OMDB, verifica tu internet'];
            }
            
            http_response_code(503);
            return ['Response' => 'False', 'Error' => 'Error de conexión a OMDB, verifica tu internet'];
        }
        
        if ($httpCode === 401) {
            http_response_code(401);
            return ['Response' => 'False', 'Error' => 'Clave API inválida o agotada'];
        }
        
        if ($httpCode !== 200) {
            error_log("OMDB API Request failed: $url - HTTP $httpCode");
            http_response_code($httpCode);
            return ['Response' => 'False', 'Error' => "Error HTTP: $httpCode"];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("OMDB API JSON decode error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 200));
            http_response_code(502);
            return ['Response' => 'False', 'Error' => 'Respuesta inválida de OMDB'];
        }
        
        if (isset($data['Error']) && strpos($data['Error'], 'Invalid API key') !== false) {
            http_response_code(401);
            return ['Response' => 'False', 'Error' => 'Clave API inválida o agotada'];
        }
        
        return $data;
    }
    
    /**
     * Convert OMDB data to our database format
     */
    public function convertToLocalFormat($omdbData) {
        try {
            if (!isset($omdbData['Response']) || $omdbData['Response'] === 'False') {
                throw new Exception('Invalid OMDB response data');
            }
            
            if (!is_array($omdbData)) {
                throw new Exception('OMDB data is not an array');
            }
            
            $type = 'movie';
            if (isset($omdbData['Type'])) {
                $type = $omdbData['Type'] === 'series' ? 'series' : 'movie';
            }
            
            // Convert runtime to minutes with better validation
            $duration = null;
            if (isset($omdbData['Runtime']) && !empty($omdbData['Runtime']) && $omdbData['Runtime'] !== 'N/A') {
                if (preg_match('/(\d+)/', $omdbData['Runtime'], $matches)) {
                    $duration = (int)$matches[1];
                    // Validate reasonable duration (1 minute to 24 hours)
                    if ($duration < 1 || $duration > 1440) {
                        $duration = null;
                    }
                }
            }
            
            // Extract year from release date with validation
            $releaseYear = null;
            if (isset($omdbData['Year']) && !empty($omdbData['Year']) && $omdbData['Year'] !== 'N/A') {
                if (preg_match('/(\d{4})/', $omdbData['Year'], $matches)) {
                    $year = (int)$matches[1];
                    // Validate reasonable year range (1888 to current year + 5)
                    if ($year >= 1888 && $year <= (date('Y') + 5)) {
                        $releaseYear = $year;
                    }
                }
            }
            
            // Validate and sanitize IMDB rating
            $imdbRating = null;
            if (isset($omdbData['imdbRating']) && $omdbData['imdbRating'] !== 'N/A') {
                $rating = (float)$omdbData['imdbRating'];
                if ($rating >= 0 && $rating <= 10) {
                    $imdbRating = $rating;
                }
            }
            
            // Validate and sanitize Metascore
            $metascore = null;
            if (isset($omdbData['Metascore']) && $omdbData['Metascore'] !== 'N/A') {
                $score = (int)$omdbData['Metascore'];
                if ($score >= 0 && $score <= 100) {
                    $metascore = $score;
                }
            }
            
            return [
                'title' => $this->sanitizeText($omdbData['Title'] ?? ''),
                'description' => $this->sanitizeText($omdbData['Plot'] ?? ''),
                'type' => $type,
                'genre' => $this->sanitizeText($omdbData['Genre'] ?? ''),
                'release_year' => $releaseYear,
                'duration' => $duration,
                'rating' => $this->sanitizeText($omdbData['Rated'] ?? 'Not Rated'),
                'poster_url' => $this->validateUrl($omdbData['Poster'] ?? ''),
                'imdb_id' => $this->sanitizeText($omdbData['imdbID'] ?? ''),
                'imdb_rating' => $imdbRating,
                'director' => $this->sanitizeText($omdbData['Director'] ?? ''),
                'actors' => $this->sanitizeText($omdbData['Actors'] ?? ''),
                'awards' => $this->sanitizeText($omdbData['Awards'] ?? ''),
                'box_office' => $this->sanitizeText($omdbData['BoxOffice'] ?? ''),
                'country' => $this->sanitizeText($omdbData['Country'] ?? ''),
                'language' => $this->sanitizeText($omdbData['Language'] ?? ''),
                'metascore' => $metascore,
                'production' => $this->sanitizeText($omdbData['Production'] ?? ''),
                'writer' => $this->sanitizeText($omdbData['Writer'] ?? '')
            ];
            
        } catch (Exception $e) {
            error_log("OMDB Data Conversion Error: " . $e->getMessage() . " - Data: " . print_r($omdbData, true));
            throw new Exception('Failed to convert OMDB data: ' . $e->getMessage());
        }
    }
    
    // Helper methods for data sanitization and validation
    private function sanitizeText($text) {
        if (empty($text) || $text === 'N/A') {
            return '';
        }
        
        // Remove any potential HTML/script tags and normalize whitespace
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Limit length to prevent database issues
        return mb_substr($text, 0, 1000, 'UTF-8');
    }
    
    private function validateUrl($url) {
        if (empty($url) || $url === 'N/A') {
            return '';
        }
        
        // Basic URL validation
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }
        
        // Ensure HTTPS for security
        if (strpos($url, 'http://') === 0) {
            $url = str_replace('http://', 'https://', $url);
        }
        
        return $url;
    }
    
    /**
     * Download and save poster image
     */
    public function downloadPoster($posterUrl, $contentId) {
        if (empty($posterUrl) || $posterUrl === 'N/A') {
            return null;
        }
        
        if (filter_var($posterUrl, FILTER_VALIDATE_URL) === false) {
            error_log("Invalid poster URL: $posterUrl");
            return null;
        }
        
        $uploadDir = 'uploads/posters/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            
            $htaccess_content = "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\nOptions -Indexes\n";
            file_put_contents($uploadDir . '.htaccess', $htaccess_content);
        }
        
        $extension = pathinfo(parse_url($posterUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }
        
        $filename = 'omdb_poster_' . $contentId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $posterUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15, // Reduced timeout for image downloads
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'StreamFlix-Admin/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_MAXFILESIZE => 10 * 1024 * 1024, // Limit file size to 10MB
        ]);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($imageData !== false && $httpCode === 200 && !empty($imageData)) {
            // Validate content type
            $validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($contentType, $validTypes)) {
                error_log("Invalid image content type: $contentType for URL: $posterUrl");
                return null;
            }
            
            // Validate image size
            if (strlen($imageData) > 10 * 1024 * 1024) { // 10MB limit
                error_log("Image too large: " . strlen($imageData) . " bytes for URL: $posterUrl");
                return null;
            }
            
            if (file_put_contents($filepath, $imageData)) {
                // Verify the saved file is a valid image
                $imageInfo = getimagesize($filepath);
                if ($imageInfo !== false) {
                    return $filepath;
                } else {
                    unlink($filepath); // Remove invalid file
                    error_log("Downloaded file is not a valid image: $posterUrl");
                }
            }
        } else {
            error_log("Failed to download poster: $posterUrl - HTTP: $httpCode - Error: $error");
        }
        
        return null;
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $result = $this->getByImdbId('tt0111161'); // The Shawshank Redemption
            return isset($result['Response']) && $result['Response'] === 'True';
        } catch (Exception $e) {
            error_log("OMDB Connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function checkConnectivity() {
        $host = 'www.omdbapi.com';
        $port = 80;
        $timeout = 5;
        
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$connection) {
            error_log("OMDB Connectivity check failed: $errstr ($errno)");
            return false;
        }
        
        fclose($connection);
        return true;
    }
    
    private function validateApiKey() {
        // Test with a simple request to validate API key
        $testUrl = $this->baseUrl . '?i=tt0111161&apikey=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT => 'StreamFlix-Admin/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            return false;
        }
        
        $data = json_decode($response, true);
        return isset($data['Response']) && $data['Response'] === 'True';
    }
}
?>
