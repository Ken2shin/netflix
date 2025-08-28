<?php
require_once 'config/config.php';
require_once 'config/database.php';

$image_handler_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'image-handler.php';
if (file_exists($image_handler_path)) {
    require_once $image_handler_path;
} else {
    // Create ImageHandler class if it doesn't exist
    if (!class_exists('ImageHandler')) {
        class ImageHandler {
            public static function forceDisplayPoster($content, $size = 'medium') {
                if (!empty($content['poster_url'])) {
                    return $content['poster_url'];
                }
                if (!empty($content['backdrop_url'])) {
                    return $content['backdrop_url'];
                }
                return '/placeholder.svg?height=400&width=300&text=' . urlencode($content['title'] ?? 'No Image');
            }
            
            public static function displayMoviePoster($poster_url, $title = '', $class = '') {
                if (!empty($poster_url)) {
                    return '<img src="' . htmlspecialchars($poster_url) . '" alt="' . htmlspecialchars($title) . '" class="' . htmlspecialchars($class) . '">';
                }
                return '<div class="placeholder-poster">No Image Available</div>';
            }
        }
    }
}

requireAuth();

$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($content_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$content_id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Obtener contenido relacionado
    $stmt = $conn->prepare("SELECT * FROM content WHERE type = ? AND id != ? ORDER BY RAND() LIMIT 6");
    $stmt->execute([$content['type'], $content_id]);
    $related_content = $stmt->fetchAll();
    
    $currentUser = getCurrentUser();
    $currentProfile = getCurrentProfile();
    
} catch (Exception $e) {
    error_log("Error en content-details: " . $e->getMessage());
    $error_message = "Error loading content. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - Netflix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }
        
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            padding: 15px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        
        .netflix-logo {
            height: 25px;
        }
        
        .main-nav {
            display: flex;
            gap: 20px;
        }
        
        .main-nav a {
            color: #e5e5e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: color 0.4s;
        }
        
        .main-nav a:hover {
            color: #b3b3b3;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-menu {
            position: relative;
            cursor: pointer;
        }
        
        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(0,0,0,0.9);
            border: 1px solid #333;
            border-radius: 4px;
            min-width: 160px;
            display: none;
            z-index: 1001;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            font-size: 13px;
            transition: background-color 0.3s;
        }
        
        .dropdown-menu a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .hero-section {
            position: relative;
            height: 70vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8));
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            padding: 80px 4% 0;
        }
        
        .hero-content {
            max-width: 50%;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }
        
        .hero-meta {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: #ccc;
        }
        
        .hero-description {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-play {
            background: white;
            color: black;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-play:hover {
            background: rgba(255,255,255,0.8);
            color: black;
        }
        
        .btn-info {
            background: rgba(109, 109, 110, 0.7);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-info:hover {
            background: rgba(109, 109, 110, 0.4);
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 3rem 4rem 1.5rem;
        }
        
        .content-row {
            display: flex;
            gap: 1rem;
            padding: 0 4rem;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .content-row::-webkit-scrollbar {
            display: none;
        }
        
        .content-item {
            flex: 0 0 200px;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .content-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .content-poster {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px 12px 0 0;
        }
        
        .content-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .poster-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }
        
        .content-item-info {
            padding: 1rem;
        }
        
        .content-item-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .content-item-meta {
            font-size: 0.8rem;
            color: #ccc;
            opacity: 0.8;
        }
        
        .back-button {
            position: fixed;
            top: 100px;
            left: 2rem;
            z-index: 1000;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: background 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .back-button:hover {
            background: rgba(0,0,0,0.9);
        }
        
        /* Enhanced mobile responsive design with better spacing and organization */
        @media (max-width: 768px) {
            .header {
                padding: 15px 1rem;
                backdrop-filter: blur(10px);
            }
            
            .header-left {
                gap: 15px;
            }
            
            .netflix-logo {
                height: 22px;
            }
            
            .main-nav {
                gap: 12px;
            }
            
            .main-nav a {
                font-size: 13px;
                padding: 8px 0;
            }
            
            .hero-section {
                padding: 100px 1.5rem 2rem;
                height: auto;
                min-height: 60vh;
            }
            
            .hero-content {
                max-width: 100%;
                text-align: left;
            }
            
            .hero-title {
                font-size: 2.2rem;
                margin-bottom: 1rem;
                line-height: 1.2;
            }
            
            .hero-meta {
                font-size: 1rem;
                margin-bottom: 1.2rem;
                line-height: 1.4;
            }
            
            .hero-description {
                font-size: 1.1rem;
                line-height: 1.5;
                margin-bottom: 2rem;
                max-width: none;
            }
            
            .hero-buttons {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .btn-play, .btn-info {
                padding: 1rem 2rem;
                font-size: 1.1rem;
                justify-content: center;
                width: 100%;
                max-width: 100%;
                min-height: 50px;
                border-radius: 8px;
            }
            
            .section-title {
                margin: 3rem 1.5rem 1.5rem;
                font-size: 1.6rem;
            }
            
            .content-row {
                padding: 0 1.5rem;
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .content-item {
                flex: 0 0 160px;
                min-height: 50px;
                border-radius: 12px;
            }
            
            .content-poster {
                height: 240px;
                border-radius: 12px 12px 0 0;
            }
            
            .content-item-info {
                padding: 1rem;
            }
            
            .content-item-title {
                font-size: 0.9rem;
                line-height: 1.3;
                margin-bottom: 0.5rem;
            }
            
            .content-item-meta {
                font-size: 0.8rem;
                opacity: 0.8;
            }
            
            .back-button {
                top: 90px;
                left: 1.5rem;
                padding: 0.8rem;
                font-size: 1.1rem;
                min-width: 50px;
                min-height: 50px;
                border-radius: 50%;
                backdrop-filter: blur(10px);
            }
        }
        
        /* Enhanced video player for mobile with forced landscape orientation */
        .video-player-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: black;
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .video-player-overlay.active {
            display: flex;
        }
        
        .video-player-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .video-controls-overlay {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .video-player-overlay:hover .video-controls-overlay,
        .video-controls-overlay.show {
            opacity: 1;
        }
        
        .video-control-btn {
            background: rgba(0,0,0,0.8);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            min-width: 50px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        
        .video-control-btn:hover {
            background: rgba(0,0,0,0.9);
        }
        
        .close-video-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            min-width: 50px;
            min-height: 50px;
            z-index: 10001;
        }
        
        /* Fixed mobile landscape video optimization and forced horizontal orientation */
        @media screen and (max-width: 768px) {
            .video-player-overlay.active {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: black;
                z-index: 9999;
                transform: rotate(0deg);
            }
            
            .video-player {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            
            /* Force landscape mode styles */
            .video-player-overlay.active .video-player {
                width: 100vh;
                height: 100vw;
                transform: rotate(90deg);
                transform-origin: center;
                position: absolute;
                top: 50%;
                left: 50%;
                margin-left: -50vh;
                margin-top: -50vw;
            }
            
            .video-controls-overlay {
                bottom: 30px;
                gap: 20px;
                transform: translateX(-50%) rotate(90deg);
                transform-origin: center;
            }
            
            .video-control-btn {
                padding: 15px 20px;
                font-size: 16px;
                min-width: 60px;
                min-height: 60px;
            }
            
            .close-video-btn {
                top: 30px;
                right: 30px;
                padding: 15px;
                font-size: 20px;
                min-width: 60px;
                min-height: 60px;
                transform: rotate(90deg);
            }
        }
        
        /* Prevent automatic backups and improve performance */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Enhanced mobile touch interactions */
        @media (max-width: 768px) {
            .content-item {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .content-item:active {
                transform: scale(0.95);
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            }
            
            .btn-play:active, .btn-info:active {
                transform: scale(0.98);
            }
            
            /* Improve scrolling performance */
            .content-row {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }
        }
        
        /* Added styles for OMDB information display */
        .content-details-section {
            padding: 2rem 4rem;
            background: rgba(0,0,0,0.8);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .detail-item {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #e50914;
        }
        
        .detail-label {
            font-weight: 600;
            color: #ccc;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            color: white;
            font-size: 1rem;
            line-height: 1.4;
        }
        
        .imdb-rating {
            display: inline-flex;
            align-items: center;
            background: #f5c518;
            color: black;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 1rem;
        }
        
        .metascore {
            display: inline-flex;
            align-items: center;
            background: #66cc33;
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .awards-section {
            background: linear-gradient(135deg, rgba(229, 9, 20, 0.1), rgba(0,0,0,0.3));
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
            border: 1px solid rgba(229, 9, 20, 0.2);
        }
        
        .cast-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .cast-member {
            background: rgba(255,255,255,0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .content-details-section {
                padding: 2rem 1.5rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cast-list {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .cast-member {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <img src="assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
            <nav class="main-nav">
                <a href="dashboard.php">Inicio</a>
                <a href="series.php">Series</a>
                <a href="movies.php">Películas</a>
                <a href="my-list.php">Mi lista</a>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="profile-menu">
                <img src="assets/images/avatars/<?php echo htmlspecialchars($currentProfile['avatar']); ?>" 
                     alt="<?php echo htmlspecialchars($currentProfile['name']); ?>" 
                     class="profile-avatar">
                <div class="dropdown-menu">
                    <a href="profiles.php">Cambiar perfil</a>
                    <a href="account.php">Mi cuenta</a>
                    <?php if ($currentUser['is_admin']): ?>
                        <a href="admin-dashboard.php">Panel Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <button class="back-button" onclick="history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>

    <div class="hero-section" style="
        <?php 
        try {
            $background_image = ImageHandler::forceDisplayPoster($content, 'large');
            if ($background_image && !strpos($background_image, 'placeholder.svg')): 
        ?>
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8)), url('<?php echo htmlspecialchars($background_image); ?>');
        <?php 
            endif;
        } catch (Exception $e) {
            error_log("Error displaying background image: " . $e->getMessage());
        }
        ?>
    ">
        <div class="hero-content">
            <?php if (isset($error_message)): ?>
                <div class="error-message" style="color: #e50914; background: rgba(229, 9, 20, 0.1); padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <h1 class="hero-title"><?php echo htmlspecialchars($content['title'] ?? 'Content Not Found'); ?></h1>
            <div class="hero-meta">
                <i class="fas fa-film"></i> <?php echo htmlspecialchars($content['type'] === 'movie' ? 'Película' : 'Serie'); ?>
                <?php if (!empty($content['release_year'])): ?>
                    | <?php echo $content['release_year']; ?>
                <?php endif; ?>
                <?php if (!empty($content['duration'])): ?>
                    | <?php echo $content['duration']; ?>min
                <?php endif; ?>
                <?php if (!empty($content['rating'])): ?>
                    | <?php echo htmlspecialchars($content['rating']); ?>
                <?php endif; ?>
                
                <?php if (!empty($content['imdb_rating'])): ?>
                    <span class="imdb-rating">
                        <i class="fab fa-imdb me-1"></i><?php echo $content['imdb_rating']; ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($content['metascore'])): ?>
                    <span class="metascore">
                        <?php echo $content['metascore']; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <p class="hero-description">
                <?php echo htmlspecialchars(!empty($content['plot']) ? $content['plot'] : ($content['description'] ?? 'No description available.')); ?>
            </p>
            
            <div class="hero-buttons">
                <a href="play-movie.php?id=<?php echo $content_id; ?>" class="btn-play">
                    <i class="fas fa-play"></i> Reproducir
                </a>
                <button class="btn-info" onclick="showMoreInfo()">
                    <i class="fas fa-info-circle"></i> Más información
                </button>
            </div>
        </div>
    </div>

    <!-- Added detailed information section with OMDB data -->
    <?php if ($content['data_source'] === 'omdb' && (!empty($content['director']) || !empty($content['actors']) || !empty($content['awards']))): ?>
        <div class="content-details-section">
            <h2 class="section-title">Información detallada</h2>
            
            <div class="details-grid">
                <?php if (!empty($content['director'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Director</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['director']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($content['writer'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Guionista</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['writer']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($content['country'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">País</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['country']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($content['language'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Idioma</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['language']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($content['box_office'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Taquilla</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['box_office']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($content['production'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Productora</div>
                        <div class="detail-value"><?php echo htmlspecialchars($content['production']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($content['actors'])): ?>
                <div class="detail-item" style="margin-top: 2rem;">
                    <div class="detail-label">Reparto principal</div>
                    <div class="cast-list">
                        <?php 
                        $actors = explode(',', $content['actors']);
                        foreach ($actors as $actor): 
                            $actor = trim($actor);
                            if (!empty($actor)):
                        ?>
                            <span class="cast-member"><?php echo htmlspecialchars($actor); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($content['awards']) && $content['awards'] !== 'N/A'): ?>
                <div class="awards-section">
                    <div class="detail-label">
                        <i class="fas fa-trophy me-2"></i>Premios y reconocimientos
                    </div>
                    <div class="detail-value"><?php echo htmlspecialchars($content['awards']); ?></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($related_content)): ?>
        <h2 class="section-title">Contenido similar</h2>
        <div class="content-row">
            <?php foreach ($related_content as $item): ?>
                <div class="content-item" onclick="location.href='content-details.php?id=<?php echo $item['id']; ?>'">
                    <div class="content-poster">
                        <?php 
                        $poster_path = ImageHandler::forceDisplayPoster($item, 'medium');
                        ?>
                        
                        <?php if (!empty($poster_path)): ?>
                            <img src="<?php echo htmlspecialchars($poster_path); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 loading="lazy"
                                 onerror="this.parentElement.innerHTML='<div class=\'poster-placeholder\'><i class=\'fas fa-film\' style=\'font-size: 2rem; color: #666;\'></i></div>'">
                        <?php else: ?>
                            <div class="poster-placeholder">
                                <i class="fas fa-film" style="font-size: 2rem; color: #666;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="content-item-info">
                        <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="content-item-meta">
                            <?php echo htmlspecialchars($item['type'] === 'movie' ? 'Película' : 'Serie'); ?> • <?php echo $item['release_year'] ?? 'N/A'; ?>
                            <!-- Show IMDB rating in related content if available -->
                            <?php if (!empty($item['imdb_rating'])): ?>
                                • <i class="fab fa-imdb"></i> <?php echo $item['imdb_rating']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Added video player overlay for mobile optimization -->
    <div class="video-player-overlay" id="videoPlayerOverlay">
        <button class="close-video-btn" onclick="closeVideoPlayer()">
            <i class="fas fa-times"></i>
        </button>
        <div class="video-player-container">
            <video class="video-player" id="mainVideoPlayer" controls>
                <source src="/placeholder.svg" type="video/mp4">
                Tu navegador no soporta el elemento de video.
            </video>
            <div class="video-controls-overlay">
                <button class="video-control-btn" onclick="togglePlayPause()">
                    <i class="fas fa-play" id="playPauseIcon"></i>
                </button>
                <button class="video-control-btn" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Menú de perfil
        document.querySelector('.profile-menu').addEventListener('click', function() {
            this.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.profile-menu')) {
                document.querySelector('.dropdown-menu').classList.remove('show');
            }
        });

        function showMoreInfo() {
            const content = <?php echo json_encode($content); ?>;
            let infoText = 'Información adicional:\n\n';
            
            if (content.director) {
                infoText += 'Director: ' + content.director + '\n';
            }
            if (content.writer) {
                infoText += 'Guionista: ' + content.writer + '\n';
            }
            if (content.actors) {
                infoText += 'Reparto: ' + content.actors + '\n';
            }
            if (content.country) {
                infoText += 'País: ' + content.country + '\n';
            }
            if (content.language) {
                infoText += 'Idioma: ' + content.language + '\n';
            }
            if (content.imdb_rating) {
                infoText += 'Rating IMDB: ' + content.imdb_rating + '/10\n';
            }
            if (content.awards && content.awards !== 'N/A') {
                infoText += 'Premios: ' + content.awards + '\n';
            }
            
            if (infoText === 'Información adicional:\n\n') {
                infoText = 'Información adicional del contenido disponible próximamente.';
            }
            
            alert(infoText);
        }

        function enableMobileLandscape() {
            if (window.innerWidth <= 768) {
                // Force landscape orientation for video
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('landscape').catch(err => {
                        console.log('Orientation lock not supported:', err);
                    });
                }
                
                // Add mobile-specific video handling
                document.body.classList.add('mobile-video-mode');
            }
        }
        
        function disableMobileLandscape() {
            if (screen.orientation && screen.orientation.unlock) {
                screen.orientation.unlock();
            }
            document.body.classList.remove('mobile-video-mode');
        }
        
        // Enhanced mobile navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Improve mobile scrolling
            if (window.innerWidth <= 768) {
                document.body.style.overscrollBehavior = 'none';
                
                // Add touch-friendly interactions
                const contentItems = document.querySelectorAll('.content-item');
                contentItems.forEach(item => {
                    item.addEventListener('touchstart', function() {
                        this.style.transform = 'scale(0.95)';
                    }, { passive: true });
                    
                    item.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }, { passive: true });
                });
                
                // Show video controls on touch
                document.addEventListener('touchstart', function() {
                    if (document.getElementById('videoPlayerOverlay').classList.contains('active')) {
                        document.querySelector('.video-controls-overlay').classList.add('show');
                        setTimeout(() => {
                            document.querySelector('.video-controls-overlay').classList.remove('show');
                        }, 4000);
                    }
                });
            }
        });
        
        // Handle orientation changes
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                window.scrollTo(0, 0);
            }, 100);
        });

        let videoPlayer = null;
        let isVideoPlaying = false;
        
        function openVideoPlayer(videoSrc) {
            const overlay = document.getElementById('videoPlayerOverlay');
            const video = document.getElementById('mainVideoPlayer');
            
            video.src = videoSrc;
            overlay.classList.add('active');
            videoPlayer = video;
            
            // Force landscape on mobile
            if (window.innerWidth <= 768) {
                forceLandscapeOrientation();
                document.body.style.overflow = 'hidden';
            }
            
            // Auto-hide controls after 3 seconds
            setTimeout(() => {
                document.querySelector('.video-controls-overlay').classList.remove('show');
            }, 3000);
        }
        
        function closeVideoPlayer() {
            const overlay = document.getElementById('videoPlayerOverlay');
            const video = document.getElementById('mainVideoPlayer');
            
            video.pause();
            video.src = '';
            overlay.classList.remove('active');
            videoPlayer = null;
            isVideoPlaying = false;
            
            // Restore orientation
            if (window.innerWidth <= 768) {
                restoreOrientation();
                document.body.style.overflow = '';
            }
        }
        
        function togglePlayPause() {
            if (!videoPlayer) return;
            
            const icon = document.getElementById('playPauseIcon');
            if (videoPlayer.paused) {
                videoPlayer.play();
                icon.className = 'fas fa-pause';
                isVideoPlaying = true;
            } else {
                videoPlayer.pause();
                icon.className = 'fas fa-play';
                isVideoPlaying = false;
            }
        }
        
        function toggleFullscreen() {
            if (!videoPlayer) return;
            
            if (videoPlayer.requestFullscreen) {
                videoPlayer.requestFullscreen();
            } else if (videoPlayer.webkitRequestFullscreen) {
                videoPlayer.webkitRequestFullscreen();
            } else if (videoPlayer.msRequestFullscreen) {
                videoPlayer.msRequestFullscreen();
            }
        }
        
        function forceLandscapeOrientation() {
            if (screen.orientation && screen.orientation.lock) {
                screen.orientation.lock('landscape').catch(err => {
                    console.log('[v0] Orientation lock not supported:', err);
                });
            }
            
            // Alternative method for older browsers
            if (window.screen && window.screen.lockOrientation) {
                window.screen.lockOrientation('landscape');
            }
        }
        
        function restoreOrientation() {
            if (screen.orientation && screen.orientation.unlock) {
                screen.orientation.unlock();
            }
            
            if (window.screen && window.screen.unlockOrientation) {
                window.screen.unlockOrientation();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Disable automatic backups by preventing certain browser behaviors
            document.addEventListener('beforeunload', function(e) {
                // Only show warning if video is playing
                if (isVideoPlaying) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
            
            // Improve mobile performance
            if (window.innerWidth <= 768) {
                document.body.style.overscrollBehavior = 'none';
                document.body.style.touchAction = 'pan-y';
                
                // Enhanced touch interactions
                const contentItems = document.querySelectorAll('.content-item');
                contentItems.forEach(item => {
                    item.addEventListener('touchstart', function(e) {
                        this.style.transform = 'scale(0.95)';
                    }, { passive: true });
                    
                    item.addEventListener('touchend', function(e) {
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }, { passive: true });
                });
                
                // Show video controls on touch
                document.addEventListener('touchstart', function() {
                    if (document.getElementById('videoPlayerOverlay').classList.contains('active')) {
                        document.querySelector('.video-controls-overlay').classList.add('show');
                        setTimeout(() => {
                            document.querySelector('.video-controls-overlay').classList.remove('show');
                        }, 4000);
                    }
                });
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const playButton = document.querySelector('.btn-play');
            if (playButton) {
                playButton.addEventListener('click', function(e) {
                    // Let the link navigate normally to play-movie.php
                    console.log('[v0] Navigating to play-movie.php');
                });
            }
        });
    </script>
</body>
</html>
