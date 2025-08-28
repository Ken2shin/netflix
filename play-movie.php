<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAuth();

$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($content_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

try {
    $conn = getConnection();
    
    // Obtener detalles del contenido
    $stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$content_id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header('Location: dashboard.php');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error en play-movie: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}

function validateAndProcessVideoUrl($url) {
    if (empty($url)) {
        return false;
    }
    
    // Remove any whitespace
    $url = trim($url);
    
    // Handle YouTube URLs and convert to direct video URLs when possible
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
        // For YouTube, we'll use the embed URL which is more reliable
        $url = "https://www.youtube.com/embed/" . $video_id . "?autoplay=1&controls=0&rel=0&showinfo=0&modestbranding=1";
    }
    
    // Check if it's a valid URL format
    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^https?:\/\//', $url)) {
        // If it doesn't start with http/https, add https://
        if (!preg_match('/^\//', $url)) {
            $url = 'https://' . $url;
        }
    }
    
    // Validate the URL again after processing
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    return $url;
}

$processed_video_url = validateAndProcessVideoUrl($content['video_url']);
$is_youtube = strpos($processed_video_url, 'youtube.com') !== false || strpos($processed_video_url, 'youtu.be') !== false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproduciendo: <?php echo htmlspecialchars($content['title']); ?> - Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }
        
        .player-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            background: #000;
        }

        /* Fixed video player display and Netflix intro animation */
        .netflix-intro {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease;
        }

        .netflix-intro.hidden {
            opacity: 0;
            pointer-events: none;
            display: none;
        }

        .netflix-intro video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Added movie preview/trailer styles */
        .preview-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 500;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .preview-info {
            position: absolute;
            bottom: 100px;
            left: 60px;
            right: 60px;
            color: white;
            z-index: 501;
        }

        .preview-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .preview-description {
            font-size: 1.2rem;
            line-height: 1.4;
            margin-bottom: 2rem;
            max-width: 600px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .preview-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-play-movie, .btn-more-info {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-play-movie {
            background: white;
            color: black;
        }

        .btn-play-movie:hover {
            background: rgba(255,255,255,0.75);
        }

        .btn-more-info {
            background: rgba(109, 109, 110, 0.7);
            color: white;
        }

        .btn-more-info:hover {
            background: rgba(109, 109, 110, 0.4);
        }
        
        /* Enhanced video player with proper display and mobile landscape orientation */
        .video-player {
            width: 100%;
            height: 100%;
            background: #000;
            object-fit: contain;
            display: block;
        }

        .youtube-player {
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
            display: block;
        }

        /* Added player controls styles */
        .player-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 100;
        }
        
        .player-container:hover .player-controls,
        .player-controls.show {
            opacity: 1;
        }
        
        .controls-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .play-btn, .volume-btn, .fullscreen-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .play-btn:hover, .volume-btn:hover, .fullscreen-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .progress-container {
            flex: 1;
            margin: 0 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: #e50914;
            border-radius: 3px;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .progress-handle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: #e50914;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .progress-bar:hover .progress-handle {
            opacity: 1;
        }
        
        .time-display {
            font-size: 14px;
            color: #ccc;
            min-width: 100px;
            text-align: center;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(0,0,0,0.9);
        }
        
        .video-info {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            padding: 10px 15px;
            border-radius: 4px;
            z-index: 1000;
        }
        
        .video-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            background: #111;
        }
        
        .video-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #666;
        }
        
        .video-placeholder h2 {
            margin-bottom: 1rem;
            color: #fff;
        }
        
        .video-placeholder p {
            color: #ccc;
            max-width: 500px;
            line-height: 1.5;
        }
        
        /* Improved loading spinner positioning and visibility */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 200;
            display: none;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid #e50914;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Improved center play button positioning */
        .center-play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            border: none;
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            font-size: 30px;
            cursor: pointer;
            z-index: 150;
            transition: all 0.3s ease;
            display: none;
        }
        
        .center-play-btn:hover {
            background: rgba(0,0,0,0.9);
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .center-play-btn.show {
            display: block;
        }

        /* Enhanced error message styling with better z-index */
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(229, 9, 20, 0.95);
            color: white;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            z-index: 300;
            max-width: 500px;
            display: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .error-message h3 {
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 600;
        }

        .error-message p {
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 14px;
        }

        .error-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .retry-btn, .back-btn-error {
            background: #fff;
            color: #e50914;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .retry-btn:hover, .back-btn-error:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
        }

        .back-btn-error {
            background: transparent;
            color: #fff;
            border: 2px solid #fff;
        }

        .back-btn-error:hover {
            background: #fff;
            color: #e50914;
        }

        /* Hide controls for YouTube videos */
        .youtube-mode .player-controls {
            display: none;
        }

        /* Enhanced mobile landscape orientation with automatic rotation */
        @media screen and (max-width: 768px) {
            .player-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                z-index: 9999;
                background: #000;
            }
            
            .player-container.playing {
                transform: rotate(90deg);
                transform-origin: center center;
                width: 100vh;
                height: 100vw;
                top: 50vh;
                left: 50vw;
                margin-left: -50vh;
                margin-top: -50vw;
            }
            
            .video-player, .youtube-player {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            
            .player-controls {
                transform: rotate(90deg);
                transform-origin: center;
                position: fixed;
                bottom: 20px;
                left: 50%;
                margin-left: -100px;
                width: 200px;
            }
        }

        /* Added skip intro button styles and positioning */
        .skip-intro {
            position: absolute;
            bottom: 100px;
            right: 50px;
            background: rgba(42, 42, 42, 0.8);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            z-index: 1001;
            transition: all 0.3s ease;
            display: block;
        }

        .skip-intro:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.8);
            transform: scale(1.05);
        }

        .skip-intro.hidden {
            display: none;
        }

        /* Fixed video container display issues */
        #videoContainer {
            width: 100%;
            height: 100%;
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #videoContainer video,
        #videoContainer iframe {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="player-container <?php echo $is_youtube ? 'youtube-mode' : ''; ?>" id="playerContainer">
        <!-- Fixed Netflix intro with proper audio and autoplay -->
        <div class="netflix-intro" id="netflixIntro">
            <video id="introVideo" autoplay muted>
                <source src="assets/videos/netflix-intro.mp4" type="video/mp4">
                Tu navegador no soporta la reproducción de video.
            </video>
            <!-- Skip intro button now hides automatically after 5 seconds -->
            <button class="skip-intro" id="skipIntroBtn" onclick="skipIntro()">Saltar introducción</button>
        </div>

        <!-- Added movie preview/trailer section -->
        <div class="preview-container" id="previewContainer">
            <div class="preview-info">
                <h1 class="preview-title"><?php echo htmlspecialchars($content['title']); ?></h1>
                <p class="preview-description">
                    <?php echo htmlspecialchars($content['description'] ?? 'Disfruta de esta increíble película o serie. Una experiencia cinematográfica única te espera.'); ?>
                </p>
                <div class="preview-buttons">
                    <button class="btn-play-movie" onclick="startMainVideo()">
                        <i class="fas fa-play"></i> Reproducir
                    </button>
                    <button class="btn-more-info" onclick="showMoreInfo()">
                        <i class="fas fa-info-circle"></i> Más información
                    </button>
                </div>
            </div>
        </div>
        
        <button class="back-btn" onclick="goBack()">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
        
        <div class="video-info">
            <strong><?php echo htmlspecialchars($content['title']); ?></strong>
        </div>
        
        <!-- Improved loading spinner with better state management -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
        </div>

        <!-- Enhanced error message container with better state management -->
        <div class="error-message" id="errorMessage">
            <h3><i class="fas fa-exclamation-triangle"></i> Error de Reproducción</h3>
            <p id="errorText">No se pudo cargar el video. Verificando la URL del video...</p>
            <div class="error-buttons">
                <button class="retry-btn" onclick="retryVideo()">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
                <button class="back-btn-error" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
        </div>
        
        <!-- Improved center play button with better state management -->
        <button class="center-play-btn" id="centerPlayBtn">
            <i class="fas fa-play"></i>
        </button>
        
        <div id="videoContainer">
            <?php if ($processed_video_url): ?>
                <?php if ($is_youtube): ?>
                    <!-- YouTube iframe for better YouTube video support -->
                    <iframe 
                        class="youtube-player" 
                        id="youtubePlayer"
                        src="<?php echo htmlspecialchars($processed_video_url); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <!-- Enhanced video element with proper source handling -->
                    <video 
                        class="video-player" 
                        id="videoPlayer" 
                        preload="metadata"
                        crossorigin="anonymous"
                        playsinline
                        controls="false"
                    >
                        <source src="<?php echo htmlspecialchars($processed_video_url); ?>" type="video/mp4">
                        <source src="<?php echo htmlspecialchars($processed_video_url); ?>" type="video/webm">
                        <source src="<?php echo htmlspecialchars($processed_video_url); ?>" type="video/ogg">
                        Tu navegador no soporta la reproducción de video HTML5.
                    </video>
                <?php endif; ?>
            <?php else: ?>
                <div class="video-placeholder">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>URL de Video Inválida</h2>
                    <p>La URL del video para "<?php echo htmlspecialchars($content['title']); ?>" no es válida o está vacía. Por favor, contacta al administrador para corregir este problema.</p>
                    <button class="back-btn" onclick="goBack()" style="position: static; margin-top: 20px;">
                        Volver al contenido
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Only show controls for non-YouTube videos -->
        <?php if (!$is_youtube): ?>
        <div class="player-controls" id="playerControls">
            <div class="controls-row">
                <button class="play-btn" id="playPauseBtn" onclick="togglePlay()">
                    <i class="fas fa-play" id="playIcon"></i>
                </button>
                <button class="volume-btn" id="volumeBtn" onclick="toggleMute()">
                    <i class="fas fa-volume-up" id="volumeIcon"></i>
                </button>
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar" onclick="seek(event)">
                        <div class="progress-fill" id="progressFill"></div>
                        <div class="progress-handle" id="progressHandle"></div>
                    </div>
                </div>
                <span class="time-display" id="timeDisplay">0:00 / 0:00</span>
                <button class="fullscreen-btn" id="fullscreenBtn" onclick="toggleFullscreen()">
                    <i class="fas fa-expand" id="fullscreenIcon"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const video = document.getElementById('videoPlayer');
        const youtubePlayer = document.getElementById('youtubePlayer');
        const playIcon = document.getElementById('playIcon');
        const volumeIcon = document.getElementById('volumeIcon');
        const fullscreenIcon = document.getElementById('fullscreenIcon');
        const progressFill = document.getElementById('progressFill');
        const progressHandle = document.getElementById('progressHandle');
        const timeDisplay = document.getElementById('timeDisplay');
        const playerControls = document.getElementById('playerControls');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const centerPlayBtn = document.getElementById('centerPlayBtn');
        const playerContainer = document.getElementById('playerContainer');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        let netflixIntro = document.getElementById('netflixIntro');
        let introVideo = document.getElementById('introVideo');
        let skipButton = document.getElementById('skipIntroBtn');
        let videoContainer = document.getElementById('videoContainer');
        
        const videoUrl = "<?php echo addslashes($processed_video_url); ?>";
        const isYoutube = videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be');
        
        console.log('[v0] Video URL:', videoUrl);
        console.log('[v0] Is YouTube:', isYoutube);

        if (introVideo) {
            introVideo.addEventListener('loadeddata', () => {
                console.log('[v0] Netflix intro loaded');
                // Unmute after a brief delay to ensure audio plays
                setTimeout(() => {
                    introVideo.muted = false;
                    introVideo.volume = 0.7;
                }, 100);
            });

            introVideo.addEventListener('ended', () => {
                console.log('[v0] Netflix intro ended, hiding skip button');
                skipButton.classList.add('hidden');
                netflixIntro.classList.add('hidden');
                setTimeout(() => {
                    showMainVideo();
                }, 500);
            });
            
            setTimeout(() => {
                if (skipButton && !netflixIntro.classList.contains('hidden')) {
                    console.log('[v0] Auto-hiding skip button after 5 seconds');
                    skipButton.classList.add('hidden');
                }
            }, 5000);

            introVideo.play().then(() => {
                console.log('[v0] Netflix intro playing with audio');
                introVideo.muted = false;
                introVideo.volume = 0.7;
            }).catch(e => {
                console.log('[v0] Autoplay blocked, user interaction required');
                // Show a play button overlay if autoplay fails
                const playOverlay = document.createElement('div');
                playOverlay.innerHTML = `
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                                background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; 
                                text-align: center; cursor: pointer; z-index: 1002;">
                        <i class="fas fa-play" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>Haz clic para comenzar</p>
                    </div>
                `;
                playOverlay.onclick = () => {
                    introVideo.muted = false;
                    introVideo.volume = 0.7;
                    introVideo.play();
                    playOverlay.remove();
                };
                netflixIntro.appendChild(playOverlay);
            });
        }

        function skipIntro() {
            console.log('[v0] Skipping Netflix intro');
            if (introVideo) {
                introVideo.pause();
                introVideo.currentTime = 0;
            }
            skipButton.classList.add('hidden');
            netflixIntro.classList.add('hidden');
            setTimeout(() => {
                showMainVideo();
            }, 500);
        }

        function showMainVideo() {
            console.log('[v0] Showing main video');
            loadingSpinner.style.display = 'block';
            
            if (isYoutube) {
                setupYouTubePlayer();
            } else {
                setupVideoPlayer();
            }
            
            forceLandscapeOnMobile();
        }

        function setupVideoPlayer() {
            console.log('[v0] Setting up regular video player');
            if (videoContainer && videoUrl) {
                const video = videoContainer.querySelector('video');
                if (video) {
                    video.src = videoUrl;
                    video.controls = true;
                    video.autoplay = true;
                    
                    video.addEventListener('loadstart', () => {
                        console.log('[v0] Video loading started');
                        loadingSpinner.style.display = 'none';
                        playerContainer.classList.add('playing');
                    });
                    
                    video.addEventListener('canplay', () => {
                        console.log('[v0] Video can play');
                        loadingSpinner.style.display = 'none';
                        errorMessage.style.display = 'none';
                    });
                    
                    video.addEventListener('error', (e) => {
                        console.error('[v0] Video error:', e);
                        loadingSpinner.style.display = 'none';
                        showVideoError();
                    });
                    
                    video.addEventListener('play', () => {
                        forceLandscapeOnMobile();
                    });
                } else {
                    showVideoError();
                }
            } else {
                showVideoError();
            }
        }

        function setupYouTubePlayer() {
            console.log('[v0] Setting up YouTube player');
            const youtubeId = extractYouTubeId(videoUrl);
            if (youtubeId && videoContainer) {
                const iframe = videoContainer.querySelector('iframe');
                if (iframe) {
                    iframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1&controls=1&rel=0&modestbranding=1`;
                    loadingSpinner.style.display = 'none';
                    playerContainer.classList.add('playing');
                } else {
                    showVideoError();
                }
            } else {
                showVideoError();
            }
        }

        function extractYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        function forceLandscapeOnMobile() {
            if (window.innerWidth <= 768) {
                console.log('[v0] Forcing landscape orientation on mobile');
                
                // Try to lock orientation using Screen Orientation API
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('landscape').catch(err => {
                        console.log('[v0] Could not lock orientation:', err);
                    });
                }
                
                // Fallback: CSS transform for landscape effect
                const videoElement = document.querySelector('.video-player, .youtube-player');
                if (videoElement) {
                    videoElement.style.transform = 'rotate(0deg)';
                    videoElement.style.width = '100vw';
                    videoElement.style.height = '100vh';
                    videoElement.style.objectFit = 'contain';
                }
                
                // Hide UI elements in landscape
                document.body.classList.add('landscape-mode');
            }
        }

        function showVideoError() {
            console.error('[v0] Showing video error');
            loadingSpinner.style.display = 'none';
            errorMessage.style.display = 'block';
            document.getElementById('errorText').textContent = 
                'No se pudo cargar el video. Verifica que la URL del video sea válida: ' + videoUrl;
        }

        function retryVideo() {
            console.log('[v0] Retrying video');
            errorMessage.style.display = 'none';
            showMainVideo();
        }

        function goBack() {
            console.log('[v0] Going back to content details');
            if (screen.orientation && screen.orientation.unlock) {
                screen.orientation.unlock();
            }
            document.body.classList.remove('landscape-mode');
            
            // Stop any playing video
            if (video && !video.paused) {
                video.pause();
            }
            if (introVideo && !introVideo.paused) {
                introVideo.pause();
            }
            
            // Navigate back to content details
            window.location.href = 'content-details.php?id=<?php echo $content_id; ?>';
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('[v0] Page loaded, starting Netflix intro');
            if (introVideo) {
                introVideo.play().catch(e => {
                    console.log('[v0] Autoplay blocked, showing play button');
                    skipIntro(); // Skip directly to main video if autoplay is blocked
                });
            } else {
                showMainVideo();
            }
        });

        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                if (window.innerWidth <= 768 && playerContainer.classList.contains('playing')) {
                    forceLandscapeOnMobile();
                }
            }, 100);
        });
    </script>
</body>
</html>
