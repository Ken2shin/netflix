<?php
class NetflixAPI {
    private $baseUrl;
    private $apiKey;
    private $authToken;
    
    public function __construct() {
        $this->baseUrl = 'https://api.netflix-clone.com/v1';
        $this->apiKey = 'your-api-key-here';
        $this->authToken = null;
    }
    
    public function setAuthToken($token) {
        $this->authToken = $token;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ];
        
        if ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode >= 400) {
            error_log("API Request failed: $url - HTTP $httpCode");
            return null;
        }
        
        return json_decode($response, true);
    }
    
    // Autenticación
    public function login($email, $password) {
        return $this->makeRequest('/auth/login', 'POST', [
            'email' => $email,
            'password' => $password
        ]);
    }
    
    public function register($username, $email, $password) {
        return $this->makeRequest('/auth/register', 'POST', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
    }
    
    public function logout() {
        return $this->makeRequest('/auth/logout', 'POST');
    }
    
    // Contenido
    public function getAllMedia() {
        $response = $this->makeRequest('/media');
        return $response['data'] ?? [];
    }
    
    public function getMediaById($mediaId) {
        return $this->makeRequest("/media/$mediaId");
    }
    
    public function searchMedia($query) {
        $response = $this->makeRequest('/media/search?q=' . urlencode($query));
        return $response['data'] ?? [];
    }
    
    public function getMediaByType($type) {
        $response = $this->makeRequest("/media?type=$type");
        return $response['data'] ?? [];
    }
    
    // Watchlist
    public function getWatchlist($userId) {
        $response = $this->makeRequest("/users/$userId/watchlist");
        return $response['data'] ?? [];
    }
    
    public function addToWatchlist($mediaId, $userId) {
        return $this->makeRequest("/users/$userId/watchlist", 'POST', [
            'media_id' => $mediaId
        ]);
    }
    
    public function removeFromWatchlist($mediaId, $userId) {
        return $this->makeRequest("/users/$userId/watchlist/$mediaId", 'DELETE');
    }
    
    // Historial
    public function getUserHistory($userId) {
        $response = $this->makeRequest("/users/$userId/history");
        return $response['data'] ?? [];
    }
    
    public function addToHistory($userId, $mediaId) {
        return $this->makeRequest("/users/$userId/history", 'POST', [
            'media_id' => $mediaId,
            'watched_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Recomendaciones
    public function getRecommendations($userId) {
        $response = $this->makeRequest("/users/$userId/recommendations");
        return $response['data'] ?? [];
    }
    
    // Streaming
    public function streamMedia($mediaId, $userId) {
        return $this->makeRequest('/stream', 'POST', [
            'media_id' => $mediaId,
            'user_id' => $userId
        ]);
    }
    
    // Pagos
    public function processPayment($paymentData) {
        return $this->makeRequest('/payments', 'POST', $paymentData);
    }
}
?>
