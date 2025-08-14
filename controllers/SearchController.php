<?php
require_once 'config/database.php';
require_once 'models/Content.php';

class SearchController {
    private $db;
    private $contentModel;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->contentModel = new Content($this->db);
    }
    
    public function search($query, $type = 'all', $genre = null, $limit = 20, $offset = 0) {
        try {
            $results = $this->contentModel->searchContent($query, $type, $genre, $limit, $offset);
            return [
                'success' => true,
                'results' => $results,
                'total' => $this->contentModel->getSearchCount($query, $type, $genre)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getPopularSearches($limit = 10) {
        try {
            $sql = "SELECT search_term, COUNT(*) as search_count 
                   FROM search_history 
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY search_term 
                   ORDER BY search_count DESC 
                   LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function saveSearchHistory($profileId, $searchTerm) {
        try {
            $sql = "INSERT INTO search_history (profile_id, search_term, created_at) 
                   VALUES (:profile_id, :search_term, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':profile_id', $profileId);
            $stmt->bindParam(':search_term', $searchTerm);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getSuggestions($query, $limit = 5) {
        try {
            $results = $this->contentModel->getSuggestions($query, $limit);
            return $results;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
