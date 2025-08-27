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
        $url = "https://www.youtube.com/embed/" . $video_id . "?autoplay=1&controls=0&rel=0&showinfo=0&modestbranding=1&iv_load_policy=3&cc_load_policy=0&fs=0&disablekb=1&loop=1&playlist=" . $video_id;
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

        /* Enhanced Netflix intro animation styles for better presentation */
        .netflix-intro {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.8s ease;
        }

        .netflix-intro.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .netflix-intro video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .skip-intro {
            position: absolute;
            bottom: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .skip-intro:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        /* Enhanced movie preview/trailer styles */
        .preview-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 100%);
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 60px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
            z-index: 100;
        }

        /* Added background video for preview */
        .preview-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .preview-container.show {
            opacity: 1;
            visibility: visible;
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

        /* Added modal styles for more info */
        .info-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .info-modal.show {
            display: flex;
        }

        .info-modal-content {
            background: #181818;
            border-radius: 8px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            color: white;
            position: relative;
        }

        .info-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .info-modal h2 {
            margin-bottom: 1rem;
            color: #e50914;
        }

        .info-modal p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .info-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .info-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 4px;
        }

        .info-item strong {
            color: #e50914;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            background: #000;
        }

        /* Enhanced YouTube iframe styling to completely hide all YouTube elements */
        .youtube-player {
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
            pointer-events: auto;
        }

        /* Enhanced YouTube iframe styling */
        .youtube-player {
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
        }
        
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

        /* Hide all YouTube branding and interface elements */
        .youtube-mode .player-controls {
            display: none !important;
        }

        .youtube-mode .video-info {
            display: none !important;
        }

        /* Hide controls for YouTube videos */
        .youtube-mode .player-controls {
            display: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="player-container <?php echo $is_youtube ? 'youtube-mode' : ''; ?>" id="playerContainer">
        <!-- Enhanced Netflix intro with better error handling -->
        <div class="netflix-intro" id="netflixIntro">
            <video id="introVideo" muted autoplay preload="auto">
                <source src="assets/videos/netflix-intro.mp4" type="video/mp4">
            </video>
            <button class="skip-intro" onclick="skipIntro()">Saltar introducción</button>
        </div>

        <!-- Enhanced movie preview section -->
        <div class="preview-container" id="previewContainer">
            <!-- Added background video for preview -->
            <?php if ($is_youtube): ?>
                <iframe class="preview-video" id="previewVideo"
                    src="<?php echo $processed_video_url; ?>&autoplay=1&mute=1&controls=0&showinfo=0&rel=0&modestbranding=1&iv_load_policy=3&fs=0&disablekb=1&playsinline=1&start=0&end=60"
                    frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>
                </iframe>
            <?php else: ?>
                <video class="preview-video" id="previewVideo" autoplay muted loop>
                    <source src="<?php echo htmlspecialchars($content['video_url']); ?>" type="video/mp4">
                </video>
            <?php endif; ?>
            
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
        
        <!-- Added info modal -->
        <div class="info-modal" id="infoModal">
            <div class="info-modal-content">
                <button class="info-modal-close" onclick="closeMoreInfo()">&times;</button>
                <h2><?php echo htmlspecialchars($content['title']); ?></h2>
                <p><?php echo htmlspecialchars($content['description'] ?? 'Una experiencia cinematográfica única te espera con este increíble contenido.'); ?></p>
                
                <div class="info-details">
                    <div class="info-item">
                        <strong>Género:</strong><br>
                        <?php echo htmlspecialchars($content['genre'] ?? 'Entretenimiento'); ?>
                    </div>
                    <div class="info-item">
                        <strong>Año:</strong><br>
                        <?php echo htmlspecialchars($content['year'] ?? date('Y')); ?>
                    </div>
                    <div class="info-item">
                        <strong>Duración:</strong><br>
                        <?php echo htmlspecialchars($content['duration'] ?? 'Variable'); ?>
                    </div>
                    <div class="info-item">
                        <strong>Calificación:</strong><br>
                        <?php echo htmlspecialchars($content['rating'] ?? 'Para toda la familia'); ?>
                    </div>
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
        
        <?php if ($processed_video_url): ?>
            <?php if ($is_youtube): ?>
                <!-- Enhanced YouTube iframe with complete branding removal -->
                <iframe 
                    class="youtube-player" 
                    id="youtubePlayer"
                    src="<?php echo htmlspecialchars($processed_video_url); ?>"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    style="pointer-events: auto;">
                </iframe>
            <?php else: ?>
                <!-- Enhanced video element for non-YouTube videos -->
                <video 
                    class="video-player" 
                    id="videoPlayer" 
                    preload="metadata"
                    crossorigin="anonymous"
                    playsinline
                    controls="false"
                >
                    <?php
                    $video_url = $processed_video_url;
                    
                    // Extract file extension from URL (handle query parameters)
                    $parsed_url = parse_url($video_url);
                    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                    $file_extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    
                    // If no extension found, try to detect from URL patterns
                    if (empty($file_extension)) {
                        if (strpos($video_url, '.mp4') !== false) $file_extension = 'mp4';
                        elseif (strpos($video_url, '.webm') !== false) $file_extension = 'webm';
                        elseif (strpos($video_url, '.ogg') !== false) $file_extension = 'ogg';
                        else $file_extension = 'mp4'; // Default fallback
                    }
                    
                    // Enhanced MIME type mapping
                    $mime_types = [
                        'mp4' => 'video/mp4',
                        'webm' => 'video/webm',
                        'ogg' => 'video/ogg',
                        'ogv' => 'video/ogg',
                        'avi' => 'video/x-msvideo',
                        'mov' => 'video/quicktime',
                        'wmv' => 'video/x-ms-wmv',
                        'flv' => 'video/x-flv',
                        'mkv' => 'video/x-matroska',
                        'm4v' => 'video/mp4',
                        '3gp' => 'video/3gpp',
                        'ts' => 'video/mp2t'
                    ];
                    
                    $mime_type = isset($mime_types[$file_extension]) ? $mime_types[$file_extension] : 'video/mp4';
                    ?>
                    
                    <!-- Primary source -->
                    <source src="<?php echo htmlspecialchars($video_url); ?>" type="<?php echo $mime_type; ?>">
                    
                    <!-- Fallback sources with different MIME types for better compatibility -->
                    <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
                    <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/webm">
                    <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/ogg">
                    
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
        
        <!-- Only show controls for non-YouTube videos -->
        <div class="player-controls" id="playerControls">
            <div class="progress-bar" onclick="seek(event)">
                <div class="progress-fill" id="progressFill"></div>
                <div class="progress-handle" id="progressHandle"></div>
            </div>
            <div class="controls-bottom">
                <div class="controls-left">
                    <button onclick="togglePlay()">
                        <i class="fas fa-play" id="playIcon"></i>
                    </button>
                    <button onclick="toggleMute()">
                        <i class="fas fa-volume-up" id="volumeIcon"></i>
                    </button>
                    <span class="time-display" id="timeDisplay">0:00 / 0:00</span>
                </div>
                <div class="controls-right">
                    <button onclick="toggleFullscreen()">
                        <i class="fas fa-expand" id="fullscreenIcon"></i>
                    </button>
                </div>
            </div>
        </div>
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

        const netflixIntro = document.getElementById('netflixIntro');
        const introVideo = document.getElementById('introVideo');
        const previewContainer = document.getElementById('previewContainer');
        const infoModal = document.getElementById('infoModal');

        let controlsTimeout;
        let isPlaying = false;
        let loadingTimeout;
        let retryCount = 0;
        const maxRetries = 3;
        const isYoutube = <?php echo $is_youtube ? 'true' : 'false'; ?>;

        const videoUrl = '<?php echo addslashes($processed_video_url); ?>';
        console.log('[v0] Video URL:', videoUrl);
        console.log('[v0] Is YouTube:', isYoutube);

        function skipIntro() {
            console.log('[v0] Skipping Netflix intro');
            if (introVideo) {
                introVideo.pause();
                introVideo.currentTime = 0;
            }
            netflixIntro.classList.add('hidden');
            setTimeout(() => {
                netflixIntro.style.display = 'none';
                showPreview();
            }, 800);
        }

        function showPreview() {
            console.log('[v0] Showing movie preview');
            if (previewContainer) {
                previewContainer.classList.add('show');
                console.log('[v0] Preview container shown');
                
                setTimeout(() => {
                    if (previewContainer && previewContainer.classList.contains('show')) {
                        console.log('[v0] Auto-starting main video after 1 minute preview');
                        startMainVideo();
                    }
                }, 60000); // 1 minute
            }
        }

        function startMainVideo() {
            console.log('[v0] Starting main video playback');
            if (previewContainer) {
                previewContainer.classList.remove('show');
                setTimeout(() => {
                    initializeMainVideo();
                }, 500);
            }
        }

        function showMoreInfo() {
            if (infoModal) {
                infoModal.classList.add('show');
            }
        }

        function closeMoreInfo() {
            if (infoModal) {
                infoModal.classList.remove('show');
            }
        }

        document.addEventListener('click', function(event) {
            if (event.target === infoModal) {
                closeMoreInfo();
            }
        });

        function initializeMainVideo() {
            console.log('[v0] Initializing main video player');
            
            if (isYoutube && youtubePlayer) {
                console.log('[v0] Starting YouTube video');
                resetPlayerState();
                isPlaying = true;
                youtubePlayer.style.display = 'block';
            } else if (video) {
                console.log('[v0] Starting regular video player');
                showLoadingSpinner();
                
                if (video.readyState >= 2) {
                    console.log('[v0] Video already loaded, starting playback');
                    startVideoPlayback();
                } else {
                    console.log('[v0] Video not loaded, waiting for loadeddata event');
                    video.addEventListener('loadeddata', startVideoPlayback, { once: true });
                    video.addEventListener('error', handleVideoLoadError, { once: true });
                    
                    setTimeout(() => {
                        if (video.readyState < 2) {
                            console.log('[v0] Video loading timeout, attempting to start anyway');
                            startVideoPlayback();
                        }
                    }, 5000);
                }
            }
        }

        function startVideoPlayback() {
            console.log('[v0] Starting video playback');
            resetPlayerState();
            
            if (video && !isYoutube) {
                const playPromise = video.play();
                
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        console.log('[v0] Video started successfully');
                        isPlaying = true;
                        if (playIcon) playIcon.className = 'fas fa-pause';
                        hideControlsAfterDelay();
                    }).catch(e => {
                        console.error('[v0] Error starting video:', e);
                        showCenterPlayButton();
                        handleVideoError('Haz clic en el botón de reproducción para iniciar el video. Tu navegador puede requerir interacción del usuario.');
                    });
                }
            }
        }

        function handleVideoLoadError(e) {
            console.error('[v0] Video loading error:', e);
            handleVideoError('Error al cargar el video. Verifica que la URL sea válida y accesible.');
        }

        if (introVideo) {
            introVideo.volume = 0.8;
            introVideo.muted = false;
            
            introVideo.addEventListener('ended', () => {
                console.log('[v0] Netflix intro ended, proceeding to preview');
                skipIntro();
            });

            introVideo.addEventListener('error', (e) => {
                console.log('[v0] Netflix intro video error, skipping to preview:', e);
                skipIntro();
            });

            introVideo.addEventListener('canplay', () => {
                console.log('[v0] Netflix intro ready to play');
                introVideo.muted = false;
            });

            setTimeout(() => {
                if (netflixIntro && !netflixIntro.classList.contains('hidden')) {
                    console.log('[v0] Auto-skipping intro after timeout');
                    skipIntro();
                }
            }, 5000);
        }

        if (centerPlayBtn) {
            centerPlayBtn.addEventListener('click', () => {
                console.log('[v0] Center play button clicked');
                togglePlay();
            });
        }

        function resetPlayerState() {
            hideLoadingSpinner();
            hideErrorMessage();
            hideCenterPlayButton();
        }

        function showLoadingSpinner() {
            loadingSpinner.style.display = 'block';
            hideErrorMessage();
            hideCenterPlayButton();
        }

        function hideLoadingSpinner() {
            loadingSpinner.style.display = 'none';
        }

        function showErrorMessage() {
            errorMessage.style.display = 'block';
            hideLoadingSpinner();
            hideCenterPlayButton();
        }

        function hideErrorMessage() {
            errorMessage.style.display = 'none';
        }

        function showCenterPlayButton() {
            centerPlayBtn.classList.add('show');
            hideLoadingSpinner();
            hideErrorMessage();
        }

        function hideCenterPlayButton() {
            centerPlayBtn.classList.remove('show');
        }

        function togglePlay() {
            if (!video || isYoutube) return;

            if (video.paused || video.ended) {
                const playPromise = video.play();
                
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        isPlaying = true;
                        if (playIcon) playIcon.className = 'fas fa-pause';
                        hideCenterPlayButton();
                        hideControlsAfterDelay();
                    }).catch(e => {
                        console.error('[v0] Error al reproducir:', e);
                        handleVideoError('Para reproducir el video, haz clic en el botón de reproducción. Algunos navegadores bloquean la reproducción automática.');
                        showCenterPlayButton();
                    });
                }
            } else {
                video.pause();
                isPlaying = false;
                if (playIcon) playIcon.className = 'fas fa-play';
                showControls();
            }
        }

        if (video && !isYoutube) {
            video.addEventListener('loadstart', () => {
                console.log('[v0] Video load started');
                showLoadingSpinner();
            });

            video.addEventListener('loadeddata', () => {
                console.log('[v0] Video data loaded');
                updateDuration();
                hideLoadingSpinner();
            });

            video.addEventListener('canplay', () => {
                console.log('[v0] Video can play');
                hideLoadingSpinner();
            });

            video.addEventListener('play', () => {
                console.log('[v0] Video play event');
                isPlaying = true;
                if (playIcon) playIcon.className = 'fas fa-pause';
                hideCenterPlayButton();
                hideControlsAfterDelay();
            });

            video.addEventListener('pause', () => {
                console.log('[v0] Video pause event');
                isPlaying = false;
                if (playIcon) playIcon.className = 'fas fa-play';
                showControls();
            });

            video.addEventListener('timeupdate', updateProgress);

            video.addEventListener('error', (e) => {
                console.error('[v0] Video error event:', e);
                const error = video.error;
                let errorMessage = 'Error desconocido al reproducir el video.';
                
                if (error) {
                    switch (error.code) {
                        case error.MEDIA_ERR_ABORTED:
                            errorMessage = 'Reproducción abortada por el usuario.';
                            break;
                        case error.MEDIA_ERR_NETWORK:
                            errorMessage = 'Error de red al cargar el video.';
                            break;
                        case error.MEDIA_ERR_DECODE:
                            errorMessage = 'Error al decodificar el video.';
                            break;
                        case error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                            errorMessage = 'Formato de video no soportado o URL inválida.';
                            break;
                    }
                }
                
                handleVideoError(errorMessage);
            });

            video.addEventListener('stalled', () => {
                console.log('[v0] Video stalled');
                showLoadingSpinner();
            });

            video.addEventListener('waiting', () => {
                console.log('[v0] Video waiting for data');
                showLoadingSpinner();
            });

            video.addEventListener('playing', () => {
                console.log('[v0] Video playing');
                hideLoadingSpinner();
            });
        }

        function toggleMute() {
            if (!video || isYoutube) return;
            
            video.muted = !video.muted;
            if (volumeIcon) volumeIcon.className = video.muted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
        }

        function toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                if (fullscreenIcon) fullscreenIcon.className = 'fas fa-expand';
            } else {
                playerContainer.requestFullscreen().catch(e => {
                    console.error('[v0] Error al entrar en pantalla completa:', e);
                });
                if (fullscreenIcon) fullscreenIcon.className = 'fas fa-compress';
            }
        }

        function updateProgress() {
            if (!video || isYoutube) return;
            
            const progress = (video.currentTime / video.duration) * 100;
            if (progressFill) progressFill.style.width = progress + '%';
            if (progressHandle) progressHandle.style.left = progress + '%';
            
            const currentTime = formatTime(video.currentTime);
            const duration = formatTime(video.duration);
            if (timeDisplay) timeDisplay.textContent = currentTime + ' / ' + duration;
        }

        function updateDuration() {
            if (!video || !video.duration || isYoutube) return;
            
            const duration = formatTime(video.duration);
            if (timeDisplay) timeDisplay.textContent = '0:00 / ' + duration;
        }

        function seek(event) {
            if (!video || isYoutube) return;
            
            const progressBar = event.currentTarget;
            const rect = progressBar.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const width = rect.width;
            const newTime = (clickX / width) * video.duration;
            
            video.currentTime = newTime;
        }

        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            
            if (hours > 0) {
                return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (secs < 10 ? '0' : '') + secs;
            } else {
                return minutes + ':' + (secs < 10 ? '0' : '') + secs;
            }
        }

        function showControls() {
            if (playerControls && !isYoutube) {
                playerControls.style.opacity = '1';
                playerControls.style.pointerEvents = 'auto';
            }
            clearTimeout(controlsTimeout);
        }

        function hideControls() {
            if (playerControls && isPlaying) {
                playerControls.style.opacity = '0';
                playerControls.style.pointerEvents = 'none';
            }
        }

        function hideControlsAfterDelay() {
            clearTimeout(controlsTimeout);
            controlsTimeout = setTimeout(hideControls, 3000);
        }

        function handleVideoError(message) {
            console.error('[v0] Video error:', message);
            hideLoadingSpinner();
            if (errorText) errorText.textContent = message;
            showErrorMessage();
            retryCount++;
        }

        function retryVideo() {
            console.log('[v0] Retrying video, attempt:', retryCount + 1);
            if (retryCount < maxRetries) {
                hideErrorMessage();
                if (isYoutube && youtubePlayer) {
                    youtubePlayer.src = youtubePlayer.src;
                } else if (video) {
                    video.load();
                    initializeMainVideo();
                }
            } else {
                handleVideoError('Se agotaron los intentos de reproducción. Verifica tu conexión a internet.');
            }
        }

        function goBack() {
            console.log('[v0] Going back to previous page');
            if (video && !video.paused) {
                video.pause();
            }
            window.history.back();
        }

        if (playerContainer) {
            playerContainer.addEventListener('mousemove', () => {
                showControls();
                hideControlsAfterDelay();
            });

            playerContainer.addEventListener('mouseleave', () => {
                if (isPlaying) {
                    hideControls();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('[v0] Page loaded, starting Netflix intro');
            
            // Auto-start Netflix intro after a short delay
            setTimeout(() => {
                if (introVideo && netflixIntro) {
                    introVideo.play().catch(e => {
                        console.log('[v0] Auto-play blocked, skipping to preview');
                        skipIntro();
                    });
                }
            }, 1000);
        });
    </script>
</body>
</html>
