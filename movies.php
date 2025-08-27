<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireProfile();

$currentUser = getCurrentUser();
$currentProfile = getCurrentProfile();

try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM content WHERE type = 'movie' ORDER BY created_at DESC");
    $stmt->execute();
    $movies = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error en movies.php: " . $e->getMessage());
    $movies = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Películas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            padding-top: 80px;
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
        
        .page-header {
            padding: 2rem 4%;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
            margin-bottom: 4rem;
        }
        
        .movie-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .movie-card:hover {
            transform: scale(1.05);
        }
        
        .movie-poster {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .movie-poster img {
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
        
        .movie-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .movie-card:hover .movie-overlay {
            opacity: 1;
        }
        
        .movie-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .movie-meta {
            font-size: 0.8rem;
            color: #b3b3b3;
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
            margin-bottom: 2rem;
        }
        
        .empty-state a {
            background: #e50914;
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .empty-state a:hover {
            background: #f40612;
        }
        
        @media (max-width: 768px) {
            .header-left {
                gap: 20px;
            }
            
            .main-nav {
                display: none;
            }
            
            .page-header {
                padding: 2rem 2%;
            }
            
            .movies-grid {
                padding: 0 2%;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .movie-poster {
                height: 225px;
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
                <a href="movies.php" class="active">Películas</a>
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
    
    <div class="page-header">
        <h1 class="page-title">Películas</h1>
    </div>
    
    <?php if (!empty($movies)): ?>
        <div class="movies-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card" onclick="location.href='content-details.php?id=<?php echo $movie['id']; ?>'">
                    <div class="movie-poster">
                        <?php if (!empty($movie['thumbnail']) && file_exists($movie['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($movie['thumbnail']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                 loading="lazy">
                        <?php else: ?>
                            <div class="poster-placeholder">
                                <i class="fas fa-film" style="font-size: 2rem; color: #666;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-overlay">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <div class="movie-meta">
                            <?php if (!empty($movie['release_year'])): ?>
                                <span><?php echo $movie['release_year']; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movie['duration'])): ?>
                                <span> • <?php echo $movie['duration']; ?> min</span>
                            <?php endif; ?>
                            <?php if (!empty($movie['rating'])): ?>
                                <span> • <?php echo htmlspecialchars($movie['rating']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No hay películas disponibles</h3>
            <p>Agrega contenido desde el panel de administración</p>
            <?php if ($currentUser['is_admin']): ?>
                <a href="admin-dashboard.php">Ir al Panel Admin</a>
            <?php endif; ?>
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
    </script>
</body>
</html>
