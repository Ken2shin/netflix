<?php
require_once 'models/Content.php';
require_once 'models/Episode.php';
require_once 'models/ViewingHistory.php';
require_once 'middleware/auth.php';

class PlayerController {
    
    // Mostrar reproductor de película
    public function playMovie($content_id) {
        requireProfile();
        
        $content = new Content();
        $viewingHistory = new ViewingHistory();
        $profile = getCurrentProfile();
        
        $movieData = $content->getContentById($content_id);
        
        if(!$movieData || $movieData['type'] !== 'movie') {
            header('HTTP/1.0 404 Not Found');
            include 'views/404.php';
            return;
        }
        
        // Incrementar contador de visualizaciones
        $content->incrementViewCount($content_id);
        
        // Obtener progreso de visualización
        $progress = $viewingHistory->getProgress($profile['id'], $content_id);
        
        // Obtener contenido similar para sugerencias
        $similar = $content->getSimilarContent($content_id, 6);
        
        $data = [
            'content' => $movieData,
            'progress' => $progress,
            'similar' => $similar,
            'type' => 'movie'
        ];
        
        include 'views/player/video-player.php';
    }
    
    // Mostrar reproductor de episodio
    public function playEpisode($episode_id) {
        requireProfile();
        
        $episode = new Episode();
        $content = new Content();
        $viewingHistory = new ViewingHistory();
        $profile = getCurrentProfile();
        
        $episodeData = $episode->getEpisodeById($episode_id);
        
        if(!$episodeData) {
            header('HTTP/1.0 404 Not Found');
            include 'views/404.php';
            return;
        }
        
        // Obtener datos de la serie
        $seriesData = $content->getContentById($episodeData['content_id']);
        
        // Incrementar contador de visualizaciones de la serie
        $content->incrementViewCount($episodeData['content_id']);
        
        // Obtener progreso de visualización
        $progress = $viewingHistory->getProgress($profile['id'], $episodeData['content_id'], $episode_id);
        
        // Obtener siguiente episodio
        $nextEpisode = $episode->getNextEpisode($episode_id);
        
        // Obtener contenido similar para sugerencias
        $similar = $content->getSimilarContent($episodeData['content_id'], 6);
        
        $data = [
            'episode' => $episodeData,
            'series' => $seriesData,
            'progress' => $progress,
            'nextEpisode' => $nextEpisode,
            'similar' => $similar,
            'type' => 'episode'
        ];
        
        include 'views/player/video-player.php';
    }
    
    // Actualizar progreso de visualización (AJAX)
    public function updateProgress() {
        requireProfile();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        $content_id = (int)$_POST['content_id'];
        $watch_time = (int)$_POST['watch_time'];
        $total_time = (int)$_POST['total_time'];
        $episode_id = isset($_POST['episode_id']) ? (int)$_POST['episode_id'] : null;
        
        $profile = getCurrentProfile();
        $viewingHistory = new ViewingHistory();
        
        $result = $viewingHistory->updateProgress(
            $profile['id'], 
            $content_id, 
            $watch_time, 
            $total_time, 
            $episode_id
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }
    
    // Obtener siguiente episodio (AJAX)
    public function getNextEpisode() {
        requireProfile();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        $episode_id = (int)$_POST['episode_id'];
        
        $episode = new Episode();
        $nextEpisode = $episode->getNextEpisode($episode_id);
        
        header('Content-Type: application/json');
        echo json_encode($nextEpisode ?: ['error' => 'No hay siguiente episodio']);
    }
}
?>
