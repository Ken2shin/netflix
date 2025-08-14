<?php
require_once 'models/Content.php';
require_once 'models/Genre.php';
require_once 'models/Season.php';
require_once 'models/Episode.php';
require_once 'models/Watchlist.php';
require_once 'models/ViewingHistory.php';
require_once 'middleware/auth.php';

class ContentController {
    
    // Mostrar página principal con catálogo
    public function showHome() {
        requireProfile();
        
        $content = new Content();
        $viewingHistory = new ViewingHistory();
        $profile = getCurrentProfile();
        
        // Obtener diferentes categorías de contenido
        $featured = $content->getFeaturedContent(1); // Solo uno para el banner principal
        $trending = $content->getTrendingContent(20);
        $recent = $content->getRecentlyAdded(20);
        $topRated = $content->getTopRated(20);
        $movies = $content->getContentByType('movie', 20);
        $series = $content->getContentByType('series', 20);
        $continueWatching = $viewingHistory->getContinueWatching($profile['id'], 10);
        
        $data = [
            'featured' => $featured,
            'trending' => $trending,
            'recent' => $recent,
            'topRated' => $topRated,
            'movies' => $movies,
            'series' => $series,
            'continueWatching' => $continueWatching
        ];
        
        include 'views/home.php';
    }
    
    // Mostrar detalles de contenido
    public function showContentDetails($content_id) {
        requireProfile();
        
        $content = new Content();
        $season = new Season();
        $watchlist = new Watchlist();
        $viewingHistory = new ViewingHistory();
        $profile = getCurrentProfile();
        
        $contentData = $content->getContentById($content_id);
        
        if(!$contentData) {
            header('HTTP/1.0 404 Not Found');
            include 'views/404.php';
            return;
        }
        
        $similar = $content->getSimilarContent($content_id, 12);
        $isInWatchlist = $watchlist->isInWatchlist($profile['id'], $content_id);
        $progress = $viewingHistory->getProgress($profile['id'], $content_id);
        
        $seasons = [];
        if($contentData['type'] === 'series') {
            $seasons = $season->getSeasonsByContent($content_id);
        }
        
        $data = [
            'content' => $contentData,
            'similar' => $similar,
            'seasons' => $seasons,
            'isInWatchlist' => $isInWatchlist,
            'progress' => $progress
        ];
        
        include 'views/content/details.php';
    }
    
    // Mostrar contenido por género
    public function showGenre($genre_slug, $page = 1) {
        requireProfile();
        
        $genre = new Genre();
        $content = new Content();
        
        $genreData = $genre->getGenreBySlug($genre_slug);
        
        if(!$genreData) {
            header('HTTP/1.0 404 Not Found');
            include 'views/404.php';
            return;
        }
        
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $contentList = $content->getContentByGenre($genreData['id'], $limit, $offset);
        
        $data = [
            'genre' => $genreData,
            'content' => $contentList,
            'currentPage' => $page
        ];
        
        include 'views/content/genre.php';
    }
    
    // Mostrar mi lista
    public function showWatchlist($page = 1) {
        requireProfile();
        
        $watchlist = new Watchlist();
        $profile = getCurrentProfile();
        
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $watchlistContent = $watchlist->getWatchlist($profile['id'], $limit, $offset);
        $totalCount = $watchlist->countWatchlist($profile['id']);
        
        $data = [
            'content' => $watchlistContent,
            'totalCount' => $totalCount,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit)
        ];
        
        include 'views/content/watchlist.php';
    }
    
    // Agregar/remover de mi lista (AJAX)
    public function toggleWatchlist() {
        requireProfile();
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        $content_id = (int)$_POST['content_id'];
        $profile = getCurrentProfile();
        
        $watchlist = new Watchlist();
        
        if($watchlist->isInWatchlist($profile['id'], $content_id)) {
            $result = $watchlist->removeFromWatchlist($profile['id'], $content_id);
            $action = 'removed';
        } else {
            $result = $watchlist->addToWatchlist($profile['id'], $content_id);
            $action = 'added';
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'action' => $action
        ]);
    }
    
    // Buscar contenido
    public function search($query, $page = 1) {
        requireProfile();
        
        if(empty($query)) {
            header('Location: index.php');
            return;
        }
        
        $content = new Content();
        
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $results = $content->searchContent($query, $limit, $offset);
        
        $data = [
            'query' => $query,
            'results' => $results,
            'currentPage' => $page
        ];
        
        include 'views/content/search.php';
    }
}
?>
