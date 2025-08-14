<?php
require_once 'middleware/auth.php';
require_once 'controllers/ContentController.php';

$contentController = new ContentController();

// Obtener ID del contenido
$contentId = $_GET['id'] ?? null;
if (!$contentId) {
    header('Location: /home');
    exit;
}

// Obtener detalles del contenido
$content = $contentController->getContentById($contentId);
if (!$content) {
    header('Location: /home');
    exit;
}

// Obtener contenido relacionado
$relatedContent = $contentController->getRelatedContent($contentId, $content['genre_id']);

// Verificar si está en la lista del usuario
$isInWatchlist = $contentController->isInWatchlist($_SESSION['profile_id'], $contentId);

// Obtener progreso de visualización
$progress = $contentController->getViewingProgress($_SESSION['profile_id'], $contentId);

// Si es una serie, obtener temporadas y episodios
$seasons = [];
if ($content['type'] === 'series') {
    $seasons = $contentController->getSeasonsByContentId($contentId);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - StreamFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/netflix.css">
    <link rel="stylesheet" href="/assets/css/content-details.css">
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <main class="content-details">
        <!-- Hero Section -->
        <div class="hero-section" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8)), url('<?php echo htmlspecialchars($content['backdrop_image']); ?>');">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold text-white mb-3"><?php echo htmlspecialchars($content['title']); ?></h1>
                        <div class="content-meta mb-3">
                            <span class="badge bg-success me-2"><?php echo $content['rating']; ?>% Coincidencia</span>
                            <span class="text-white me-3"><?php echo $content['release_year']; ?></span>
                            <span class="badge bg-secondary me-2"><?php echo $content['age_rating']; ?></span>
                            <span class="text-white"><?php echo $content['duration']; ?> min</span>
                        </div>
                        <p class="lead text-white mb-4"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <div class="action-buttons">
                            <?php if ($content['type'] === 'movie'): ?>
                                <a href="/play-movie?id=<?php echo $content['id']; ?>" class="btn btn-light btn-lg me-3">
                                    <i class="fas fa-play me-2"></i>Reproducir
                                </a>
                            <?php else: ?>
                                <a href="/play-episode?series_id=<?php echo $content['id']; ?>&season=1&episode=1" class="btn btn-light btn-lg me-3">
                                    <i class="fas fa-play me-2"></i>Reproducir
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-light btn-lg me-3" onclick="toggleWatchlist(<?php echo $content['id']; ?>)">
                                <i class="fas fa-<?php echo $isInWatchlist ? 'check' : 'plus'; ?> me-2"></i>
                                <?php echo $isInWatchlist ? 'En mi lista' : 'Mi lista'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Info -->
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-8">
                    <?php if ($content['type'] === 'series' && !empty($seasons)): ?>
                        <!-- Episodios -->
                        <div class="episodes-section">
                            <h3 class="text-white mb-4">Episodios</h3>
                            <div class="seasons-dropdown mb-4">
                                <select class="form-select bg-dark text-white" id="seasonSelect">
                                    <?php foreach ($seasons as $season): ?>
                                        <option value="<?php echo $season['season_number']; ?>">
                                            Temporada <?php echo $season['season_number']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="episodesList">
                                <!-- Los episodios se cargarán dinámicamente -->
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="content-sidebar">
                        <h5 class="text-white mb-3">Reparto</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($content['cast']); ?></p>
                        
                        <h5 class="text-white mb-3 mt-4">Géneros</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($content['genre_name']); ?></p>
                        
                        <h5 class="text-white mb-3 mt-4">Director</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($content['director']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Relacionado -->
        <?php if (!empty($relatedContent)): ?>
        <div class="container pb-5">
            <h3 class="text-white mb-4">Más como esto</h3>
            <div class="row">
                <?php foreach ($relatedContent as $related): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="content-card">
                        <a href="/content-details?id=<?php echo $related['id']; ?>">
                            <img src="<?php echo htmlspecialchars($related['poster_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                 class="img-fluid rounded">
                        </a>
                        <h6 class="text-white mt-2"><?php echo htmlspecialchars($related['title']); ?></h6>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/netflix.js"></script>
    <script>
        // Función para alternar watchlist
        function toggleWatchlist(contentId) {
            fetch('/api/toggle-watchlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content_id: contentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Cargar episodios cuando cambie la temporada
        document.getElementById('seasonSelect')?.addEventListener('change', function() {
            const season = this.value;
            const seriesId = <?php echo $content['id']; ?>;
            
            fetch(`/api/get-episodes.php?series_id=${seriesId}&season=${season}`)
                .then(response => response.json())
                .then(data => {
                    const episodesList = document.getElementById('episodesList');
                    episodesList.innerHTML = '';
                    
                    data.episodes.forEach(episode => {
                        const episodeHtml = `
                            <div class="episode-item mb-3 p-3 bg-dark rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <img src="${episode.thumbnail}" class="img-fluid rounded" alt="${episode.title}">
                                    </div>
                                    <div class="col-md-9">
                                        <h6 class="text-white">${episode.episode_number}. ${episode.title}</h6>
                                        <p class="text-muted small">${episode.description}</p>
                                        <span class="text-muted small">${episode.duration} min</span>
                                        <a href="/play-episode?series_id=${seriesId}&season=${season}&episode=${episode.episode_number}" 
                                           class="btn btn-sm btn-outline-light ms-3">
                                            <i class="fas fa-play me-1"></i>Reproducir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                        episodesList.innerHTML += episodeHtml;
                    });
                });
        });

        // Cargar episodios de la primera temporada al cargar la página
        if (document.getElementById('seasonSelect')) {
            document.getElementById('seasonSelect').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>
