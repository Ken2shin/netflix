<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireProfile();

$pdo = getConnection();

// Obtener contenido de la lista del perfil actual
$stmt = $pdo->prepare("
    SELECT c.*, w.added_at 
    FROM content c 
    INNER JOIN watchlist w ON c.id = w.content_id 
    WHERE w.profile_id = ? 
    ORDER BY w.added_at DESC
");
$stmt->execute([$_SESSION['profile_id']]);
$watchlistContent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Lista - Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #141414;
            color: white;
            overflow-x: hidden;
        }

        .netflix-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            z-index: -2;
            animation: backgroundMove 25s ease-in-out infinite;
        }

        .netflix-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(20, 20, 20, 0.8) 50%, rgba(20, 20, 20, 0.95) 100%);
            z-index: 1;
        }

        @keyframes backgroundMove {
            0%, 100% { transform: scale(1.0) translateX(0px) translateY(0px); }
            25% { transform: scale(1.05) translateX(-20px) translateY(-10px); }
            50% { transform: scale(1.1) translateX(20px) translateY(-20px); }
            75% { transform: scale(1.05) translateX(-10px) translateY(10px); }
        }

        .header {
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

        .search-icon, .notifications-icon {
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .search-icon:hover, .notifications-icon:hover {
            color: #b3b3b3;
        }

        .profile-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            position: relative;
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
        }

        .main-content {
            position: relative;
            z-index: 10;
            padding-top: 80px;
            min-height: 100vh;
        }

        .page-header {
            padding: 2rem 4%;
            text-align: center;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
            margin-bottom: 4rem;
        }

        .content-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
        }

        .content-card:hover {
            transform: scale(1.05);
        }

        .content-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .content-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .content-card:hover .content-overlay {
            opacity: 1;
        }

        .content-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .content-meta {
            font-size: 0.8rem;
            color: #b3b3b3;
        }

        .empty-watchlist {
            text-align: center;
            padding: 4rem 2rem;
            color: #8c8c8c;
        }

        .empty-watchlist h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .empty-watchlist p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: #e50914;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background: #f40612;
        }

        @media (max-width: 768px) {
            .header-left {
                gap: 20px;
            }
            
            .main-nav {
                display: none;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .content-card img {
                height: 225px;
            }
        }
    </style>
</head>
<body>
    <div class="netflix-background"></div>
    
    <header class="header" id="netflixHeader">
        <div class="header-left">
            <img src="assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
            <nav class="main-nav">
                <a href="dashboard.php">Inicio</a>
                <a href="series.php">Series</a>
                <a href="movies.php">Películas</a>
                <a href="my-list.php" class="active">Mi lista</a>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="search-icon" onclick="toggleSearch()">🔍</div>
            <div class="notifications-icon" onclick="showNotifications()">🔔</div>
            
            <div class="profile-menu" onclick="toggleProfileMenu()">
                <img src="assets/images/avatars/<?php echo $_SESSION['profile_avatar'] ?? 'avatar1.png'; ?>" 
                     alt="<?php echo $_SESSION['profile_name'] ?? 'Perfil'; ?>" 
                     class="profile-avatar">
                <span>▼</span>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mi Lista</h1>
        </div>
        
        <?php if (!empty($watchlistContent)): ?>
            <div class="content-grid">
                <?php foreach ($watchlistContent as $item): ?>
                    <div class="content-card" onclick="viewContent(<?php echo $item['id']; ?>)">
                        <img src="/placeholder.svg?height=300&width=200&text=<?php echo urlencode($item['title']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="content-overlay">
                            <h3 class="content-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="content-meta">
                                <span><?php echo $item['release_year']; ?></span>
                                <span> • <?php echo $item['type'] === 'movie' ? $item['duration'] . ' min' : 'Serie'; ?></span>
                                <span> • <?php echo $item['rating']; ?></span>
                                <span> • <?php echo $item['genre']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-watchlist">
                <h2>Tu lista está vacía</h2>
                <p>Agrega películas y series que quieras ver más tarde</p>
                <a href="dashboard.php" class="btn-primary">Explorar contenido</a>
            </div>
        <?php endif; ?>
    </main>
    
    <script>
        function toggleSearch() {
            alert('Función de búsqueda próximamente');
        }
        
        function showNotifications() {
            alert('No tienes notificaciones nuevas');
        }
        
        function toggleProfileMenu() {
            if (confirm('¿Quieres cambiar de perfil?')) {
                window.location.href = 'profiles.php';
            }
        }
        
        function viewContent(id) {
            window.location.href = 'content-details.php?id=' + id;
        }
        
        window.addEventListener('scroll', function() {
            const header = document.getElementById('netflixHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
