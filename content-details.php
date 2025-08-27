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
    
    // Obtener contenido relacionado
    $stmt = $conn->prepare("SELECT * FROM content WHERE type = ? AND id != ? ORDER BY RAND() LIMIT 6");
    $stmt->execute([$content['type'], $content_id]);
    $related_content = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error en content-details: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}

$currentUser = getCurrentUser();
$currentProfile = getCurrentProfile();
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
            transition: transform 0.3s ease;
        }
        
        .content-item:hover {
            transform: scale(1.05);
        }
        
        .content-poster {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .content-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }
        
        .content-item-info {
            padding: 1rem;
            background: #222;
        }
        
        .content-item-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .content-item-meta {
            font-size: 0.8rem;
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 100px 2rem 0;
                height: 60vh;
            }
            
            .hero-content {
                max-width: 100%;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title {
                margin: 2rem 2rem 1rem;
            }
            
            .content-row {
                padding: 0 2rem;
            }
            
            .content-item {
                flex: 0 0 150px;
            }
            
            .content-poster {
                height: 220px;
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

    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($content['title']); ?></h1>
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
            </div>
            <p class="hero-description"><?php echo htmlspecialchars($content['description']); ?></p>
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

    <?php if (!empty($related_content)): ?>
        <h2 class="section-title">Contenido similar</h2>
        <div class="content-row">
            <?php foreach ($related_content as $item): ?>
                <div class="content-item" onclick="location.href='content-details.php?id=<?php echo $item['id']; ?>'">
                    <div class="content-poster">
                        <?php 
                        $poster_url = $item['poster_url'] ?? '/placeholder.svg?height=280&width=200';
                        ?>
                        
                        <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             loading="lazy"
                             onerror="this.src='/placeholder.svg?height=280&width=200';">
                    </div>
                    <div class="content-item-info">
                        <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="content-item-meta">
                            <?php echo htmlspecialchars($item['type'] === 'movie' ? 'Película' : 'Serie'); ?> • <?php echo $item['release_year'] ?? 'N/A'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
            alert('Información adicional del contenido disponible próximamente.');
        }
    </script>
</body>
</html>
