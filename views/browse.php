<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
requireProfile();

require_once 'controllers/ContentController.php';
require_once 'models/Content.php';
require_once 'models/Genre.php';

$contentController = new ContentController();
$languages = $contentController->getAvailableLanguages();

$content = new Content();
$genre = new Genre();

$allContent = $content->getContentByType('', 100); // Get all content
$genres = $genre->getAllGenres();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Explorar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/netflix.css">
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
        
        .filter-section {
            padding: 1rem 4%;
            margin-bottom: 2rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .filter-btn {
            background: transparent;
            border: 1px solid #333;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #e50914;
            border-color: #e50914;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
        }
        
        .content-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
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
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
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
    </style>
</head>
<body class="netflix-body">
    <?php include 'views/partials/header.php'; ?>
    
    <div class="page-header">
        <h1 class="page-title">Explorar por géneros</h1>
    </div>
    
    <div class="filter-section">
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">Todos</button>
            <button class="filter-btn" data-filter="movie">Películas</button>
            <button class="filter-btn" data-filter="series">Series</button>
            <?php foreach ($genres as $genreItem): ?>
                <button class="filter-btn" data-filter="genre-<?php echo $genreItem['id']; ?>">
                    <?php echo htmlspecialchars($genreItem['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="content-grid" id="contentGrid">
        <?php if (!empty($allContent)): ?>
            <?php foreach ($allContent as $item): ?>
                <div class="content-card" data-type="<?php echo $item['type']; ?>" data-genres="<?php echo $item['genre_ids'] ?? ''; ?>" onclick="location.href='content?id=<?php echo $item['id']; ?>'">
                    <img src="/placeholder.svg?height=300&width=200" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="content-overlay">
                        <h3 class="content-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="content-meta">
                            <span><?php echo $item['release_year']; ?></span>
                            <span> • <?php echo $item['type'] === 'movie' ? $item['duration'] . ' min' : 'Serie'; ?></span>
                            <span> • <?php echo $item['rating']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <h2>No hay contenido disponible</h2>
                <p>Agrega contenido desde el panel de administración</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const cards = document.querySelectorAll('.content-card');
                
                cards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else if (filter === 'movie' || filter === 'series') {
                        card.style.display = card.dataset.type === filter ? 'block' : 'none';
                    } else if (filter.startsWith('genre-')) {
                        const genreId = filter.replace('genre-', '');
                        const cardGenres = card.dataset.genres.split(',');
                        card.style.display = cardGenres.includes(genreId) ? 'block' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
