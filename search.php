<?php
session_start();
require_once 'middleware/auth.php';
require_once 'controllers/SearchController.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$searchController = new SearchController();
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';
$genre = $_GET['genre'] ?? null;

$results = [];
$totalResults = 0;

if (!empty($query)) {
    $searchData = $searchController->search($query, $type, $genre);
    $results = $searchData['results'] ?? [];
    $totalResults = $searchData['total'] ?? 0;
}

$popularSearches = $searchController->getPopularSearches();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($query) ? "Resultados para: $query" : "Buscar"; ?> - StreamFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/netflix.css">
    <link rel="stylesheet" href="assets/css/search.css">
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="search-container">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="search-header">
                        <h1><?php echo !empty($query) ? "Resultados para: \"$query\"" : "Buscar contenido"; ?></h1>
                        <?php if (!empty($query)): ?>
                            <p class="search-count"><?php echo $totalResults; ?> resultados encontrados</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="search-filters">
                        <div class="filter-buttons">
                            <button class="filter-btn <?php echo $type === 'all' ? 'active' : ''; ?>" data-type="all">Todo</button>
                            <button class="filter-btn <?php echo $type === 'movie' ? 'active' : ''; ?>" data-type="movie">Películas</button>
                            <button class="filter-btn <?php echo $type === 'series' ? 'active' : ''; ?>" data-type="series">Series</button>
                        </div>
                    </div>
                    
                    <?php if (!empty($results)): ?>
                        <div class="search-results">
                            <div class="row">
                                <?php foreach ($results as $content): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                        <div class="content-card" data-content-id="<?php echo $content['id']; ?>">
                                            <div class="card-image">
                                                <img src="<?php echo $content['poster_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($content['title']); ?>"
                                                     loading="lazy">
                                                <div class="card-overlay">
                                                    <div class="card-actions">
                                                        <button class="btn-play" onclick="playContent(<?php echo $content['id']; ?>, '<?php echo $content['type']; ?>')">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                        <button class="btn-info" onclick="showContentInfo(<?php echo $content['id']; ?>)">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                        <button class="btn-watchlist" onclick="toggleWatchlist(<?php echo $content['id']; ?>)">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-info">
                                                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                                <p class="content-type"><?php echo $content['type'] === 'movie' ? 'Película' : 'Serie'; ?></p>
                                                <p class="content-year"><?php echo date('Y', strtotime($content['release_date'])); ?></p>
                                                <div class="content-rating">
                                                    <span class="rating"><?php echo $content['rating']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif (!empty($query)): ?>
                        <div class="no-results">
                            <h2>No se encontraron resultados</h2>
                            <p>Intenta con otros términos de búsqueda</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($query) && !empty($popularSearches)): ?>
                        <div class="popular-searches">
                            <h2>Búsquedas populares</h2>
                            <div class="popular-tags">
                                <?php foreach ($popularSearches as $search): ?>
                                    <span class="popular-tag" onclick="searchContent('<?php echo htmlspecialchars($search['search_term']); ?>')">
                                        <?php echo htmlspecialchars($search['search_term']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/netflix.js"></script>
    <script src="assets/js/search.js"></script>
</body>
</html>
