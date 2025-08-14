<?php
require_once 'middleware/auth.php';
requireProfile();

require_once 'models/Content.php';

$content = new Content();
$series = $content->getContentByType('series', 50);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Series</title>
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
        
        .series-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 0 4%;
        }
        
        .series-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .series-card:hover {
            transform: scale(1.05);
        }
        
        .series-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .series-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .series-card:hover .series-overlay {
            opacity: 1;
        }
        
        .series-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .series-meta {
            font-size: 0.8rem;
            color: #b3b3b3;
        }
    </style>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="page-header">
        <h1 class="page-title">Series</h1>
    </div>
    
    <div class="series-grid">
        <?php if (!empty($series)): ?>
            <?php foreach ($series as $show): ?>
                <div class="series-card" onclick="location.href='content?id=<?php echo $show['id']; ?>'">
                    <img src="/placeholder.svg?height=300&width=200" alt="<?php echo htmlspecialchars($show['title']); ?>">
                    <div class="series-overlay">
                        <h3 class="series-title"><?php echo htmlspecialchars($show['title']); ?></h3>
                        <div class="series-meta">
                            <span><?php echo $show['release_year']; ?></span>
                            <span> • <?php echo $show['rating']; ?></span>
                            <span> • Serie</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <h2>No hay series disponibles</h2>
                <p>Agrega contenido desde el panel de administración</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
