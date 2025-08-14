<?php
require_once 'middleware/auth.php';
require_once 'controllers/APIController.php';

requireAuth();
requireProfile();

$apiController = new APIController();
$currentProfile = getCurrentProfile();
$currentUser = getCurrentUser();

// Obtener contenido desde la API - MÉTODO CORREGIDO
$allMedia = $apiController->getAllMedia(); // Cambiado de getAllContent() a getAllMedia()
$recommendations = $apiController->getRecommendations($currentUser['id']);
$watchlist = $apiController->getWatchlist($currentUser['id']);
$userHistory = $apiController->getUserHistory($currentUser['id']);

// Separar por tipo
$movies = array_filter($allMedia, function($item) {
    return ($item['type'] ?? 'movie') === 'movie';
});

$series = array_filter($allMedia, function($item) {
    return ($item['type'] ?? 'movie') === 'series';
});

// Contenido destacado (primer elemento)
$featuredContent = !empty($allMedia) ? $allMedia[0] : null;

// Continuar viendo (últimos 5 del historial)
$continueWatching = array_slice($userHistory, 0, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Inicio</title>
    <link rel="stylesheet" href="/assets/css/netflix.css">
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
        
        .search-container {
            display: flex;
            align-items: center;
            background: rgba(0,0,0,0.75);
            border: 1px solid white;
            border-radius: 4px;
            padding: 5px 10px;
        }
        
        .search-container input {
            background: transparent;
            border: none;
            color: white;
            outline: none;
            padding: 5px;
            width: 200px;
        }
        
        .search-container button {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
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
        
        .content-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 0 0 4px 4px;
        }
        
        .content-item:hover .content-overlay {
            opacity: 1;
        }
        
        .content-overlay h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .content-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .content-actions button {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #808080;
            background: transparent;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .content-actions button:hover {
            border-color: white;
            transform: scale(1.1);
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
            
            .search-container input {
                width: 150px;
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
            
            .content-item img {
                height: 225px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="netflix-header" id="netflixHeader">
        <div class="header-left">
            <img src="/assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
            <nav class="main-nav">
                <a href="/home" class="active">Inicio</a>
                <a href="/series">Series</a>
                <a href="/movies">Películas</a>
                <a href="/my-list">Mi lista</a>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="search-container">
                <input type="text" id="search-input" placeholder="Buscar...">
                <button id="search-btn">🔍</button>
            </div>
            
            <div class="profile-menu">
                <img src="/assets/images/avatars/<?php echo htmlspecialchars($currentProfile['avatar']); ?>" 
                     alt="<?php echo htmlspecialchars($currentProfile['name']); ?>" 
                     class="profile-avatar">
                <div class="dropdown-menu">
                    <a href="/profiles">Cambiar perfil</a>
                    <a href="/account">Mi cuenta</a>
                    <a href="/logout">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Hero Section -->
        <?php if ($featuredContent): ?>
        <section class="hero-section" style="background-image: url('<?php echo $featuredContent['backdrop_url'] ?? '/placeholder.svg?height=600&width=1200&text=' . urlencode($featuredContent['title']); ?>')">
            <div class="hero-content">
                <h1 class="hero-title"><?php echo htmlspecialchars($featuredContent['title']); ?></h1>
                <p class="hero-description"><?php echo htmlspecialchars(substr($featuredContent['description'] ?? '', 0, 200)); ?>...</p>
                
                <div class="hero-buttons">
                    <button class="btn-play" onclick="playContent(<?php echo $featuredContent['id']; ?>)">
                        ▶ Reproducir
                    </button>
                    <button class="btn-info" onclick="showInfo(<?php echo $featuredContent['id']; ?>)">
                        ℹ Más información
                    </button>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="hero-section" style="background-image: url('/placeholder.svg?height=600&width=1200&text=Netflix+Hero')">
            <div class="hero-content">
                <h1 class="hero-title">Bienvenido a Netflix</h1>
                <p class="hero-description">Disfruta de películas y series ilimitadas</p>
                
                <div class="hero-buttons">
                    <button class="btn-play" onclick="window.location.href='/browse'">
                        ▶ Explorar contenido
                    </button>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Secciones de contenido -->
        <div class="content-sections">
            <!-- Continuar viendo -->
            <?php if (!empty($continueWatching)): ?>
            <section class="content-row">
                <h2>Continuar viendo</h2>
                <div class="content-slider">
                    <?php foreach ($continueWatching as $item): ?>
                        <div class="content-item" onclick="showInfo(<?php echo $item['id']; ?>)">
                            <img src="<?php echo $item['poster_url'] ?? '/placeholder.svg?height=300&width=200&text=' . urlencode($item['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="content-overlay">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div class="content-actions">
                                    <button onclick="event.stopPropagation(); playContent(<?php echo $item['id']; ?>)" title="Reproducir">▶</button>
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?php echo $item['id']; ?>)" title="Agregar a Mi Lista">+</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Mi Lista -->
            <?php if (!empty($watchlist)): ?>
            <section class="content-row">
                <h2>Mi lista</h2>
                <div class="content-slider">
                    <?php foreach ($watchlist as $item): ?>
                        <div class="content-item" onclick="showInfo(<?php echo $item['id']; ?>)">
                            <img src="<?php echo $item['poster_url'] ?? '/placeholder.svg?height=300&width=200&text=' . urlencode($item['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="content-overlay">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div class="content-actions">
                                    <button onclick="event.stopPropagation(); playContent(<?php echo $item['id']; ?>)" title="Reproducir">▶</button>
                                    <button onclick="event.stopPropagation(); removeFromWatchlist(<?php echo $item['id']; ?>)" title="Eliminar de Mi Lista">✓</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Recomendaciones -->
            <?php if (!empty($recommendations)): ?>
            <section class="content-row">
                <h2>Recomendado para ti</h2>
                <div class="content-slider">
                    <?php foreach ($recommendations as $item): ?>
                        <div class="content-item" onclick="showInfo(<?php echo $item['id']; ?>)">
                            <img src="<?php echo $item['poster_url'] ?? '/placeholder.svg?height=300&width=200&text=' . urlencode($item['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="content-overlay">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div class="content-actions">
                                    <button onclick="event.stopPropagation(); playContent(<?php echo $item['id']; ?>)" title="Reproducir">▶</button>
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?php echo $item['id']; ?>)" title="Agregar a Mi Lista">+</button>
                                </div>
                            </div>
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
                    <?php foreach (array_slice($movies, 0, 10) as $movie): ?>
                        <div class="content-item" onclick="showInfo(<?php echo $movie['id']; ?>)">
                            <img src="<?php echo $movie['poster_url'] ?? '/placeholder.svg?height=300&width=200&text=' . urlencode($movie['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            <div class="content-overlay">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                <div class="content-actions">
                                    <button onclick="event.stopPropagation(); playContent(<?php echo $movie['id']; ?>)" title="Reproducir">▶</button>
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?php echo $movie['id']; ?>)" title="Agregar a Mi Lista">+</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php else: ?>
            <section class="content-row">
                <h2>Películas populares</h2>
                <div class="empty-state">
                    <h3>No hay películas disponibles</h3>
                    <p>El contenido se cargará desde la API externa</p>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Series populares -->
            <?php if (!empty($series)): ?>
            <section class="content-row">
                <h2>Series populares</h2>
                <div class="content-slider">
                    <?php foreach (array_slice($series, 0, 10) as $show): ?>
                        <div class="content-item" onclick="showInfo(<?php echo $show['id']; ?>)">
                            <img src="<?php echo $show['poster_url'] ?? '/placeholder.svg?height=300&width=200&text=' . urlencode($show['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($show['title']); ?>">
                            <div class="content-overlay">
                                <h3><?php echo htmlspecialchars($show['title']); ?></h3>
                                <div class="content-actions">
                                    <button onclick="event.stopPropagation(); playContent(<?php echo $show['id']; ?>)" title="Reproducir">▶</button>
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?php echo $show['id']; ?>)" title="Agregar a Mi Lista">+</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php else: ?>
            <section class="content-row">
                <h2>Series populares</h2>
                <div class="empty-state">
                    <h3>No hay series disponibles</h3>
                    <p>El contenido se cargará desde la API externa</p>
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
        
        // Funciones de interacción
        function playContent(contentId) {
            fetch('/api/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    media_id: contentId,
                    user_id: <?php echo $currentUser['id']; ?>,
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = `/play/${contentId}`;
                } else {
                    alert('Error al reproducir contenido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback: ir directamente al reproductor
                window.location.href = `/play/${contentId}`;
            });
        }
        
        function addToWatchlist(contentId) {
            fetch('/api/watchlist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    media_id: contentId,
                    user_id: <?php echo $currentUser['id']; ?>,
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Agregado a Mi Lista');
                    location.reload();
                } else {
                    alert('Error al agregar a la lista');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
        
        function removeFromWatchlist(contentId) {
            fetch('/api/watchlist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    media_id: contentId,
                    user_id: <?php echo $currentUser['id']; ?>,
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Eliminado de Mi Lista');
                    location.reload();
                } else {
                    alert('Error al eliminar de la lista');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
        
        function showInfo(contentId) {
            window.location.href = `/content/${contentId}`;
        }
        
        // Búsqueda
        document.getElementById('search-btn').addEventListener('click', function() {
            const query = document.getElementById('search-input').value.trim();
            if (query) {
                window.location.href = `/search?q=${encodeURIComponent(query)}`;
            }
        });
        
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = `/search?q=${encodeURIComponent(query)}`;
                }
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
        
        // Slider horizontal con mouse
        document.querySelectorAll('.content-slider').forEach(slider => {
            let isDown = false;
            let startX;
            let scrollLeft;
            
            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
                slider.style.cursor = 'grabbing';
            });
            
            slider.addEventListener('mouseleave', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            
            slider.addEventListener('mouseup', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 2;
                slider.scrollLeft = scrollLeft - walk;
            });
        });
    </script>
</body>
</html>
