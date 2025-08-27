<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['type'] === 'movie' ? $data['content']['title'] : $data['episode']['title'] . ' - ' . $data['series']['title']; ?> - StreamFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/player.css" rel="stylesheet">
</head>
<body class="player-body">
    <div class="video-container" id="videoContainer">
        <!-- Video Element -->
        <video 
            id="videoPlayer" 
            class="video-player"
            <?php if($data['type'] === 'movie'): ?>
                <?php if($data['content']['video_url']): ?>
                    src="uploads/videos/<?php echo $data['content']['video_url']; ?>"
                <?php else: ?>
                    src="/placeholder.mp4?title=<?php echo urlencode($data['content']['title']); ?>"
                <?php endif; ?>
            <?php else: ?>
                <?php if($data['episode']['video_url']): ?>
                    src="uploads/videos/<?php echo $data['episode']['video_url']; ?>"
                <?php else: ?>
                    src="/placeholder.mp4?title=<?php echo urlencode($data['episode']['title']); ?>"
                <?php endif; ?>
            <?php endif; ?>
            preload="metadata"
            crossorigin="anonymous"
        ></video>
        
        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
        </div>
        
        <!-- Video Controls -->
        <div class="video-controls" id="videoControls">
            <!-- Top Controls -->
            <div class="controls-top">
                <button class="btn-back" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="video-info">
                    <h1 class="video-title">
                        <?php if($data['type'] === 'movie'): ?>
                            <?php echo htmlspecialchars($data['content']['title']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($data['series']['title']); ?>
                        <?php endif; ?>
                    </h1>
                    <?php if($data['type'] === 'episode'): ?>
                        <p class="episode-info">
                            T<?php echo $data['episode']['season_number']; ?>:E<?php echo $data['episode']['episode_number']; ?> 
                            "<?php echo htmlspecialchars($data['episode']['title']); ?>"
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Center Play Button -->
            <div class="center-controls">
                <button class="btn-play-center" id="centerPlayBtn">
                    <i class="fas fa-play"></i>
                </button>
            </div>
            
            <!-- Bottom Controls -->
            <div class="controls-bottom">
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-filled" id="progressFilled"></div>
                        <div class="progress-handle" id="progressHandle"></div>
                    </div>
                    <div class="time-display">
                        <span id="currentTime">0:00</span>
                        <span id="totalTime">0:00</span>
                    </div>
                </div>
                
                <div class="control-buttons">
                    <div class="left-controls">
                        <button class="control-btn" id="playPauseBtn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="control-btn" id="rewindBtn">
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="control-btn" id="forwardBtn">
                            <i class="fas fa-forward"></i>
                        </button>
                        <div class="volume-control">
                            <button class="control-btn" id="volumeBtn">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <div class="volume-slider" id="volumeSlider">
                                <input type="range" id="volumeRange" min="0" max="100" value="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="right-controls">
                        <?php if($data['type'] === 'episode' && $data['nextEpisode']): ?>
                            <button class="control-btn" id="nextEpisodeBtn" title="Siguiente episodio">
                                <i class="fas fa-step-forward"></i>
                            </button>
                        <?php endif; ?>
                        <button class="control-btn" id="subtitlesBtn" title="Subtítulos">
                            <i class="fas fa-closed-captioning"></i>
                        </button>
                        <button class="control-btn" id="qualityBtn" title="Calidad">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="control-btn" id="fullscreenBtn">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Next Episode Preview -->
        <?php if($data['type'] === 'episode' && $data['nextEpisode']): ?>
            <div class="next-episode-preview" id="nextEpisodePreview">
                <div class="preview-content">
                    <?php if($data['nextEpisode']['thumbnail']): ?>
                        <img src="uploads/thumbnails/<?php echo $data['nextEpisode']['thumbnail']; ?>" alt="Siguiente episodio">
                    <?php else: ?>
                        <div class="placeholder-thumbnail">
                            <i class="fas fa-play"></i>
                        </div>
                    <?php endif; ?>
                    <div class="preview-info">
                        <h3>Siguiente episodio</h3>
                        <p>T<?php echo $data['nextEpisode']['season_number']; ?>:E<?php echo $data['nextEpisode']['episode_number']; ?> 
                           "<?php echo htmlspecialchars($data['nextEpisode']['title']); ?>"</p>
                        <div class="preview-buttons">
                            <button class="btn btn-light" onclick="playNextEpisode()">Reproducir</button>
                            <button class="btn btn-outline-light" onclick="hideNextEpisode()">Cancelar</button>
                        </div>
                    </div>
                </div>
                <div class="countdown" id="countdown">15</div>
            </div>
        <?php endif; ?>
        
        <!-- Quality Menu -->
        <div class="quality-menu" id="qualityMenu">
            <div class="menu-header">Calidad</div>
            <div class="menu-options">
                <div class="quality-option active" data-quality="auto">Auto</div>
                <div class="quality-option" data-quality="1080p">1080p</div>
                <div class="quality-option" data-quality="720p">720p</div>
                <div class="quality-option" data-quality="480p">480p</div>
            </div>
        </div>
        
        <!-- Subtitles Menu -->
        <div class="subtitles-menu" id="subtitlesMenu">
            <div class="menu-header">Subtítulos</div>
            <div class="menu-options">
                <div class="subtitle-option active" data-lang="off">Desactivado</div>
                <div class="subtitle-option" data-lang="es">Español</div>
                <div class="subtitle-option" data-lang="en">Inglés</div>
            </div>
        </div>
    </div>
    
    <!-- End Credits Overlay -->
    <div class="end-credits-overlay" id="endCreditsOverlay">
        <div class="credits-content">
            <h2>¿Te gustó?</h2>
            <div class="rating-buttons">
                <button class="btn-rating" data-rating="thumbs_up">
                    <i class="fas fa-thumbs-up"></i>
                </button>
                <button class="btn-rating" data-rating="thumbs_down">
                    <i class="fas fa-thumbs-down"></i>
                </button>
            </div>
            
            <div class="suggestions">
                <h3>Contenido similar</h3>
                <div class="suggestions-grid">
                    <?php foreach($data['similar'] as $item): ?>
                        <div class="suggestion-item" onclick="goToContent(<?php echo $item['id']; ?>)">
                            <?php if($item['thumbnail']): ?>
                                <img src="uploads/thumbnails/<?php echo $item['thumbnail']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php else: ?>
                                <div class="placeholder-thumbnail">
                                    <i class="fas fa-film"></i>
                                </div>
                            <?php endif; ?>
                            <div class="suggestion-info">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p><?php echo $item['release_year']; ?> • <?php echo $item['rating']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button class="btn btn-outline-light" onclick="goBack()">Volver al catálogo</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/player.js"></script>
    
    <script>
        // Configuración del reproductor
        const playerConfig = {
            contentId: <?php echo $data['type'] === 'movie' ? $data['content']['id'] : $data['series']['id']; ?>,
            episodeId: <?php echo $data['type'] === 'episode' ? $data['episode']['id'] : 'null'; ?>,
            type: '<?php echo $data['type']; ?>',
            hasNextEpisode: <?php echo ($data['type'] === 'episode' && $data['nextEpisode']) ? 'true' : 'false'; ?>,
            nextEpisodeId: <?php echo ($data['type'] === 'episode' && $data['nextEpisode']) ? $data['nextEpisode']['id'] : 'null'; ?>,
            startTime: <?php echo $data['progress'] ? $data['progress']['watch_time'] : 0; ?>,
            totalTime: <?php echo $data['type'] === 'movie' ? ($data['content']['duration'] * 60) : ($data['episode']['duration'] * 60); ?>
        };
        
        // Inicializar reproductor
        document.addEventListener('DOMContentLoaded', function() {
            initializePlayer(playerConfig);
        });
    </script>
</body>
</html>
