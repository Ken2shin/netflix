<?php
require_once 'middleware/auth.php';
requireProfile();

require_once 'models/Content.php';

$content = new Content();
$movies = $content->getContentByType('movie', 50);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Películas</title>
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
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
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
        
        .movie-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
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
    </style>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="page-header">
        <h1 class="page-title">Películas</h1>
    </div>
    
    <div class="movies-grid">
        <?php if (!empty($movies)): ?>
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card" onclick="location.href='content?id=<?php echo $movie['id']; ?>'">
                    <img src="/placeholder.svg?height=300&width=200" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <div class="movie-overlay">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <div class="movie-meta">
                            <span><?php echo $movie['release_year']; ?></span>
                            <span> • <?php echo $movie['duration']; ?> min</span>
                            <span> • <?php echo $movie['rating']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <h2>No hay películas disponibles</h2>
                <p>Agrega contenido desde el panel de administración</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
