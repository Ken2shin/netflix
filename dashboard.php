<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar autenticación
requireLogin();

try {
    // Obtener conexión a la base de datos
    $db = getConnection();
    
    // Obtener contenido destacado de forma segura
    $featured_content = null;
    try {
        // Verificar si existe la columna is_featured
        if (columnExists('content', 'is_featured')) {
            $sql = "SELECT * FROM content WHERE is_featured = 1 ORDER BY RAND() LIMIT 1";
        } else {
            $sql = "SELECT * FROM content ORDER BY RAND() LIMIT 1";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $featured_content = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo contenido destacado: " . $e->getMessage());
    }
    
    // Obtener contenido por categorías de forma segura
    $content_data = [];
    
    // Verificar qué columnas existen antes de hacer las consultas
    $hasViewCount = columnExists('content', 'view_count');
    $orderBy = $hasViewCount ? 'view_count DESC' : 'id DESC';
    
    $categories = [
        'Tendencias' => "SELECT * FROM content ORDER BY $orderBy LIMIT 10",
        'Películas populares' => "SELECT * FROM content WHERE type = 'movie' ORDER BY $orderBy LIMIT 10",
        'Series populares' => "SELECT * FROM content WHERE type = 'series' ORDER BY $orderBy LIMIT 10"
    ];
    
    foreach ($categories as $category => $query) {
        try {
            $stmt = $db->prepare($query);
            $stmt->execute();
            $content_data[$category] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo contenido para $category: " . $e->getMessage());
            $content_data[$category] = [];
        }
    }
    
} catch (Exception $e) {
    error_log("Error general en dashboard: " . $e->getMessage());
    $featured_content = null;
    $content_data = [];
}

// Obtener información del usuario de forma segura
$user_name = $_SESSION['user_name'] ?? $_SESSION['email'] ?? 'Usuario';
$is_admin = $_SESSION['is_admin'] ?? false;
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
            background: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }

        /* Header */
        .netflix-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(180deg, rgba(0,0,0,0.7) 10%, transparent);
            z-index: 1000;
            padding: 10px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.4s;
        }

        .netflix-header.scrolled {
            background-color: #141414;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .netflix-logo {
            height: 25px;
            color: #e50914;
            font-size: 24px;
            font-weight: bold;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e5e5e5;
        }

        .admin-badge {
            background: #e50914;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .logout-btn {
            background: #e50914;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #f40612;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8)), 
                        url('/placeholder.svg?height=600&width=1200&text=Netflix');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding-left: 4rem;
        }

        .hero-content {
            max-width: 500px;
            z-index: 2;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .hero-description {
            font-size: 1.2rem;
            line-height: 1.5;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-play, .btn-info {
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-play {
            background: white;
            color: black;
        }

        .btn-play:hover {
            background: rgba(255,255,255,0.8);
        }

        .btn-info {
            background: rgba(109,109,110,0.7);
            color: white;
        }

        .btn-info:hover {
            background: rgba(109,109,110,0.4);
        }

        .content-sections {
            padding: 2rem 4rem;
            margin-top: -200px;
            position: relative;
            z-index: 3;
        }

        .content-row {
            margin-bottom: 3rem;
        }

        .row-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #e5e5e5;
        }

        .content-slider {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .content-slider::-webkit-scrollbar {
            display: none;
        }

        .content-item {
            min-width: 250px;
            height: 140px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .content-item:hover {
            transform: scale(1.05);
        }

        .content-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content-placeholder {
            text-align: center;
            color: #999;
        }

        .content-placeholder i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .admin-panel-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #e50914;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            z-index: 1001;
            transition: background 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .admin-panel-btn:hover {
            background: #f40612;
        }

        .success-message {
            background: #2e7d32;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 4rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding-left: 2rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .content-sections {
                padding: 2rem;
            }
            
            .content-item {
                min-width: 200px;
                height: 120px;
            }
            
            .success-message {
                margin: 20px 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="netflix-header" id="netflixHeader">
        <div class="header-left">
            <div class="netflix-logo">NETFLIX</div>
            <nav class="main-nav">
                <a href="dashboard.php" class="active">Inicio</a>
                <a href="movies.php">Películas</a>
                <a href="series.php">Series</a>
                <a href="my-list.php">Mi lista</a>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($user_name); ?></span>
                <?php if ($is_admin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="logout-btn">Cerrar sesión</a>
        </div>
    </header>

    <?php if ($is_admin): ?>
    <button class="admin-panel-btn" onclick="window.location.href='admin-dashboard.php'" title="Panel de Administración">
        <i class="fas fa-cog"></i>
    </button>
    <?php endif; ?>

    <div class="success-message">
        <strong>¡Bienvenido a Netflix!</strong> Has iniciado sesión correctamente.
    </div>

    <div class="hero-section">
        <div class="hero-content">
            <?php if ($featured_content): ?>
                <h1 class="hero-title"><?php echo htmlspecialchars($featured_content['title']); ?></h1>
                <p class="hero-description"><?php echo htmlspecialchars(substr($featured_content['description'] ?? 'Contenido destacado', 0, 200)); ?></p>
                <div class="hero-buttons">
                    <a href="content-details.php?id=<?php echo $featured_content['id']; ?>" class="btn-play">
                        <i class="fas fa-play"></i> Reproducir
                    </a>
                    <a href="content-details.php?id=<?php echo $featured_content['id']; ?>" class="btn-info">
                        <i class="fas fa-info-circle"></i> Más información
                    </a>
                </div>
            <?php else: ?>
                <h1 class="hero-title">Bienvenido a Netflix</h1>
                <p class="hero-description">Disfruta de películas y series ilimitadas. Explora nuestro catálogo completo.</p>
                <div class="hero-buttons">
                    <a href="movies.php" class="btn-play">
                        <i class="fas fa-film"></i> Ver Películas
                    </a>
                    <a href="series.php" class="btn-info">
                        <i class="fas fa-tv"></i> Ver Series
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-sections">
        <?php foreach ($content_data as $category => $items): ?>
            <?php if (!empty($items)): ?>
                <div class="content-row">
                    <h2 class="row-title"><?php echo htmlspecialchars($category); ?></h2>
                    <div class="content-slider">
                        <?php foreach ($items as $item): ?>
                            <div class="content-item" onclick="goToContent(<?php echo $item['id']; ?>)">
                                <?php if (!empty($item['poster_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['poster_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <div class="content-placeholder">
                                        <i class="fas fa-<?php echo $item['type'] === 'movie' ? 'film' : 'tv'; ?>"></i>
                                        <div><?php echo htmlspecialchars($item['title']); ?></div>
                                        <small><?php echo ucfirst($item['type']); ?> • <?php echo $item['release_year'] ?? 'N/A'; ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if (empty(array_filter($content_data))): ?>
            <div class="content-row">
                <h2 class="row-title">Contenido</h2>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-film" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <p>No hay contenido disponible en este momento.</p>
                    <?php if ($is_admin): ?>
                        <p><a href="admin-dashboard.php" style="color: #e50914;">Agregar contenido desde el panel de administración</a></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

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

        function goToContent(id) {
            window.location.href = `content-details.php?id=${id}`;
        }
    </script>
</body>
</html>
