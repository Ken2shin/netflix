<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireProfile();

$currentUser = getCurrentUser();
$currentProfile = getCurrentProfile();

// Obtener contenido de la base de datos
try {
    $conn = getConnection();
    
    // Contenido destacado
    $stmt = $conn->prepare("SELECT * FROM content WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $featuredContent = $stmt->fetch();
    
    // Contenido reciente
    $stmt = $conn->prepare("SELECT * FROM content ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $recentContent = $stmt->fetchAll();
    
    // Películas
    $stmt = $conn->prepare("SELECT * FROM content WHERE type = 'movie' ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $movies = $stmt->fetchAll();
    
    // Series
    $stmt = $conn->prepare("SELECT * FROM content WHERE type = 'series' ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $series = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $featuredContent = null;
    $recentContent = [];
    $movies = [];
    $series = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Inicio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #141414;
            color: white;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            overflow-x: hidden;
        }
        
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(180deg, rgba(0,0,0,0.7) 10%, transparent);
            z-index: 1000;
            padding: 15px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.4s;
        }
        
        .header.scrolled {
            background-color: #141414;
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
        
        .main-nav a:hover,
        .main-nav a.active {
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
        
        .main-content {
            margin-top: 70px;
        }
        
        .hero-section {
            height: 56.25vw;
            min-height: 600px;
            max-height: 800px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(77deg, rgba(0,0,0,.6), transparent 85%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 0 4%;
            max-width: 50%;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }
        
        .hero-description {
            font-size: 1.4rem;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn-play {
            background-color: white;
            color: black;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .btn-play:hover {
            background-color: rgba(255,255,255,0.75);
        }
        
        .btn-info {
            background-color: rgba(109, 109, 110, 0.7);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .btn-info:hover {
            background-color: rgba(109, 109, 110, 0.4);
        }
        
        .content-sections {
            margin-top: -150px;
            position: relative;
            z-index: 1;
            padding: 0 4%;
        }
        
        .content-row {
            margin-bottom: 3rem;
        }
        
        .content-row h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #e5e5e5;
        }
        
        .content-slider {
            display: flex;
            gap: 0.25rem;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .content-slider::-webkit-scrollbar {
            display: none;
        }
        
        .content-item {
            flex: 0 0 auto;
            width: 200px;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .content-item:hover {
            transform: scale(1.05);
            z-index: 2;
        }
        
        .content-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .content-placeholder {
            width: 100%;
            height: 300px;
            background: #333;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #8c8c8c;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .header-left {
                gap: 20px;
            }
            
            .main-nav {
                display: none;
            }
            
            .hero-content {
                max-width: 80%;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .content-item {
                width: 150px;
            }
            
            .content-item img,
            .content-placeholder {
                height: 225px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="netflixHeader">
        <div class="header-left">
            <img src="assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
            <nav class="main-nav">
                <a href="dashboard.php" class="active">Inicio</a>
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
    
    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Hero Section -->
        <?php if ($featuredContent): ?>
        <section class="hero-section" style="background-image: url('/placeholder.svg?height=600&width=1200&text=<?php echo urlencode($featuredContent['title']); ?>')">
            <div class="hero-content">
                <h1 class="hero-title"><?php echo htmlspecialchars($featuredContent['title']); ?></h1>
                <p class="hero-description"><?php echo htmlspecialchars(substr($featuredContent['description'] ?? '', 0, 200)); ?>...</p>
                
                <div class="hero-buttons">
                    <a href="content-details.php?id=<?php echo $featuredContent['id']; ?>" class="btn-play">
                        <i class="fas fa-play"></i> Reproducir
                    </a>
                    <a href="content-details.php?id=<?php echo $featuredContent['id']; ?>" class="btn-info">
                        <i class="fas fa-info-circle"></i> Más información
                    </a>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="hero-section" style="background-image: url('/placeholder.svg?height=600&width=1200&text=Netflix+Hero')">
            <div class="hero-content">
                <h1 class="hero-title">Bienvenido a Netflix</h1>
                <p class="hero-description">Disfruta de películas y series ilimitadas</p>
                
                <div class="hero-buttons">
                    <a href="movies.php" class="btn-play">
                        <i class="fas fa-play"></i> Explorar contenido
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Secciones de contenido -->
        <div class="content-sections">
            <!-- Contenido reciente -->
            <?php if (!empty($recentContent)): ?>
            <section class="content-row">
                <h2>Agregado recientemente</h2>
                <div class="content-slider">
                    <?php foreach ($recentContent as $item): ?>
                        <div class="content-item" onclick="location.href='content-details.php?id=<?php echo $item['id']; ?>'">
                            <?php if (!empty($item['thumbnail']) && file_exists($item['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php else: ?>
                                <div class="content-placeholder">
                                    <i class="fas fa-film" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Películas populares -->
            <?php if (!empty($movies)): ?>
            <section class="content-row">
                <h2>Películas populares</h2>
                <div class="content-slider">
                    <?php foreach ($movies as $movie): ?>
                        <div class="content-item" onclick="location.href='content-details.php?id=<?php echo $movie['id']; ?>'">
                            <?php if (!empty($movie['thumbnail']) && file_exists($movie['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            <?php else: ?>
                                <div class="content-placeholder">
                                    <i class="fas fa-film" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php else: ?>
            <section class="content-row">
                <h2>Películas populares</h2>
                <div class="empty-state">
                    <h3>No hay películas disponibles</h3>
                    <p>Agrega contenido desde el panel de administración</p>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Series populares -->
            <?php if (!empty($series)): ?>
            <section class="content-row">
                <h2>Series populares</h2>
                <div class="content-slider">
                    <?php foreach ($series as $show): ?>
                        <div class="content-item" onclick="location.href='content-details.php?id=<?php echo $show['id']; ?>'">
                            <?php if (!empty($show['thumbnail']) && file_exists($show['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($show['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($show['title']); ?>">
                            <?php else: ?>
                                <div class="content-placeholder">
                                    <i class="fas fa-film" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php else: ?>
            <section class="content-row">
                <h2>Series populares</h2>
                <div class="empty-state">
                    <h3>No hay series disponibles</h3>
                    <p>Agrega contenido desde el panel de administración</p>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('netflixHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
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
    </script>
</body>
</html>
