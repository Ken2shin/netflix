<?php
require_once 'middleware/auth.php';
requireProfile();

require_once 'models/Watchlist.php';

$watchlist = new Watchlist();
$profile = getCurrentProfile();
$watchlistContent = $watchlist->getWatchlist($profile['id'], 50, 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Mi Lista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/netflix.css" rel="stylesheet">
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            padding-top: 70px;
        }
        
        .page-header {
            padding: 2rem 0;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .watchlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
        }
        
        .watchlist-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .watchlist-card:hover {
            transform: scale(1.05);
        }
        
        .watchlist-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .watchlist-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .watchlist-card:hover .watchlist-overlay {
            opacity: 1;
        }
        
        .watchlist-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .watchlist-meta {
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
    </style>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="page-header">
        <h1 class="page-title">Mi Lista</h1>
    </div>
    
    <?php if (!empty($watchlistContent)): ?>
        <div class="watchlist-grid">
            <?php foreach ($watchlistContent as $item): ?>
                <div class="watchlist-card" onclick="location.href='content?id=<?php echo $item['id']; ?>'">
                    <img src="/placeholder.svg?height=300&width=200" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="watchlist-overlay">
                        <h3 class="watchlist-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="watchlist-meta">
                            <span><?php echo $item['release_year']; ?></span>
                            <span> • <?php echo $item['type'] === 'movie' ? $item['duration'] . ' min' : 'Serie'; ?></span>
                            <span> • <?php echo $item['rating']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-watchlist">
            <h2>Tu lista está vacía</h2>
            <p>Agrega películas y series que quieras ver más tarde</p>
            <a href="home" class="btn btn-primary">Explorar contenido</a>
        </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
