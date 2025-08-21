<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireProfile();

$database = new Database();
$db = $database->getConnection();

// Obtener géneros para el filtro
$sql = "SELECT * FROM genres ORDER BY name";
$stmt = $db->prepare($sql);
$stmt->execute();
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$genre_filter = $_GET['genre'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Construir consulta
$where_conditions = ["type = 'series'"];
$params = [];

if ($genre_filter) {
    $where_conditions[] = "id IN (SELECT content_id FROM content_genres WHERE genre_id = :genre_id)";
    $params[':genre_id'] = $genre_filter;
}

if ($search_filter) {
    $where_conditions[] = "(title LIKE :search OR description LIKE :search)";
    $params[':search'] = '%' . $search_filter . '%';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT * FROM content WHERE $where_clause ORDER BY title ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$series = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Series - Netflix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/netflix.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            padding-top: 80px;
        }

        .page-header {
            padding: 2rem 4rem;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.3));
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: #ccc;
        }

        .filter-select, .filter-input {
            padding: 0.5rem;
            background: #333;
            border: 1px solid #555;
            color: white;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #e50914;
        }

        .filter-btn {
            background: #e50914;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            align-self: end;
        }

        .filter-btn:hover {
            background: #f40612;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            padding: 2rem 4rem;
        }

        .content-card {
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .content-card:hover {
            transform: scale(1.05);
        }

        .content-poster {
            width: 100%;
            height: 350px;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content-info {
            padding: 1rem;
        }

        .content-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-meta {
            color: #ccc;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .content-description {
            color: #999;
            font-size: 0.8rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 2rem;
            }
            
            .content-grid {
                padding: 2rem;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Series</h1>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="genre">Género:</label>
                <select name="genre" id="genre" class="filter-select">
                    <option value="">Todos los géneros</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo $genre['id']; ?>" 
                                <?php echo $genre_filter == $genre['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Buscar:</label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       class="filter-input" 
                       placeholder="Título o descripción..."
                       value="<?php echo htmlspecialchars($search_filter); ?>">
            </div>
            
            <button type="submit" class="filter-btn">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>

    <?php if (empty($series)): ?>
        <div class="no-results">
            <i class="fas fa-tv"></i>
            <h3>No se encontraron series</h3>
            <p>Intenta cambiar los filtros o buscar algo diferente.</p>
        </div>
    <?php else: ?>
        <div class="content-grid">
            <?php foreach ($series as $serie): ?>
                <div class="content-card" onclick="goToContent(<?php echo $serie['id']; ?>)">
                    <?php if ($serie['poster_url']): ?>
                        <img src="<?php echo $serie['poster_url']; ?>" 
                             alt="<?php echo htmlspecialchars($serie['title']); ?>" 
                             class="content-poster">
                    <?php else: ?>
                        <div class="content-poster">
                            <i class="fas fa-tv" style="font-size: 3rem; color: #666;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="content-info">
                        <div class="content-title"><?php echo htmlspecialchars($serie['title']); ?></div>
                        <div class="content-meta">
                            Serie • <?php echo $serie['release_year']; ?>
                            <?php if ($serie['rating']): ?>
                                • ⭐ <?php echo number_format($serie['rating'], 1); ?>
                            <?php endif; ?>
                        </div>
                        <div class="content-description">
                            <?php echo htmlspecialchars($serie['description']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script>
        function goToContent(id) {
            window.location.href = `content-details.php?id=${id}`;
        }
    </script>
</body>
</html>
