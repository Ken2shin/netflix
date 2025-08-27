<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAdmin();

$contentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($contentId <= 0) {
    header('Location: admin-content.php');
    exit();
}

// Obtener datos del contenido
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$contentId]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header('Location: admin-content.php?error=' . urlencode('Contenido no encontrado'));
        exit();
    }
} catch (Exception $e) {
    error_log("Error obteniendo contenido: " . $e->getMessage());
    header('Location: admin-content.php?error=' . urlencode('Error al cargar el contenido'));
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $type = sanitize($_POST['type']);
    $release_year = (int)$_POST['release_year'];
    $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
    $rating = sanitize($_POST['rating']);
    $imdb_rating = !empty($_POST['imdb_rating']) ? (float)$_POST['imdb_rating'] : null;
    $video_url = sanitize($_POST['video_url']);
    $video_platform = sanitize($_POST['video_platform']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    
    // Validaciones
    if (empty($title) || empty($description) || empty($type)) {
        $error = 'Por favor completa todos los campos obligatorios';
    } else {
        try {
            // Manejar subida de nueva imagen si se proporciona
            $thumbnail = $content['thumbnail']; // Mantener la imagen actual por defecto
            
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/thumbnails/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadPath)) {
                    // Eliminar imagen anterior si existe
                    if (!empty($content['thumbnail']) && file_exists($content['thumbnail'])) {
                        unlink($content['thumbnail']);
                    }
                    $thumbnail = $uploadPath;
                }
            }
            
            // Actualizar en base de datos
            $stmt = $conn->prepare("
                UPDATE content SET 
                    title = ?, 
                    description = ?, 
                    type = ?, 
                    release_year = ?, 
                    duration = ?, 
                    rating = ?, 
                    imdb_rating = ?, 
                    video_url = ?, 
                    video_platform = ?, 
                    thumbnail = ?, 
                    is_featured = ?, 
                    is_trending = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $title, $description, $type, $release_year, $duration, 
                $rating, $imdb_rating, $video_url, $video_platform, 
                $thumbnail, $is_featured, $is_trending, $contentId
            ])) {
                header('Location: admin-content.php?success=' . urlencode('Contenido actualizado exitosamente'));
                exit();
            } else {
                $error = 'Error al actualizar el contenido';
            }
        } catch (Exception $e) {
            error_log("Error actualizando contenido: " . $e->getMessage());
            $error = 'Error interno del servidor';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contenido - Netflix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: #1a1a1a;
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-brand h4 {
            color: #e50914;
            font-weight: 700;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 1rem 2rem;
            color: #b3b3b3;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #333;
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .top-bar {
            background: #1a1a1a;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-container {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 2rem;
        }
        
        .form-control {
            background: #333;
            border: 1px solid #555;
            color: white;
        }
        
        .form-control:focus {
            background: #333;
            border-color: #e50914;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .form-select {
            background: #333;
            border: 1px solid #555;
            color: white;
        }
        
        .form-select:focus {
            background: #333;
            border-color: #e50914;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .form-label {
            color: #b3b3b3;
            font-weight: 600;
        }
        
        .btn-admin {
            background: #e50914;
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-admin:hover {
            background: #f40612;
            color: white;
        }
        
        .btn-admin-secondary {
            background: #333;
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-admin-secondary:hover {
            background: #555;
            color: white;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        .alert-danger {
            background: #dc3545;
            color: white;
        }
        
        .current-thumbnail {
            max-width: 200px;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .platform-info {
            background: #333;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .platform-example {
            font-size: 0.9rem;
            color: #b3b3b3;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-crown"></i> Admin Panel</h4>
        </div>
        <ul class="sidebar-nav">
            <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin-content.php"><i class="fas fa-film"></i> Gestionar Contenido</a></li>
            <li><a href="admin-add-content.php"><i class="fas fa-plus"></i> Agregar Contenido</a></li>
            <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a href="admin-statistics.php"><i class="fas fa-chart-bar"></i> Estadísticas</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2>Editar Contenido</h2>
            <div>
                <a href="admin-content.php" class="btn-admin-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="logout.php" class="btn-admin">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
        
        <!-- Mensajes de error -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Formulario -->
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($content['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($content['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Tipo *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="movie" <?php echo $content['type'] === 'movie' ? 'selected' : ''; ?>>Película</option>
                                        <option value="series" <?php echo $content['type'] === 'series' ? 'selected' : ''; ?>>Serie</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="release_year" class="form-label">Año de Lanzamiento</label>
                                    <input type="number" class="form-control" id="release_year" name="release_year" 
                                           min="1900" max="2030" value="<?php echo $content['release_year']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duración (minutos)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" 
                                           min="1" value="<?php echo $content['duration']; ?>">
                                    <small class="text-muted">Solo para películas</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Clasificación</label>
                                    <select class="form-select" id="rating" name="rating">
                                        <option value="">Seleccionar...</option>
                                        <option value="G" <?php echo $content['rating'] === 'G' ? 'selected' : ''; ?>>G</option>
                                        <option value="PG" <?php echo $content['rating'] === 'PG' ? 'selected' : ''; ?>>PG</option>
                                        <option value="PG-13" <?php echo $content['rating'] === 'PG-13' ? 'selected' : ''; ?>>PG-13</option>
                                        <option value="R" <?php echo $content['rating'] === 'R' ? 'selected' : ''; ?>>R</option>
                                        <option value="NC-17" <?php echo $content['rating'] === 'NC-17' ? 'selected' : ''; ?>>NC-17</option>
                                        <option value="TV-Y" <?php echo $content['rating'] === 'TV-Y' ? 'selected' : ''; ?>>TV-Y</option>
                                        <option value="TV-Y7" <?php echo $content['rating'] === 'TV-Y7' ? 'selected' : ''; ?>>TV-Y7</option>
                                        <option value="TV-G" <?php echo $content['rating'] === 'TV-G' ? 'selected' : ''; ?>>TV-G</option>
                                        <option value="TV-PG" <?php echo $content['rating'] === 'TV-PG' ? 'selected' : ''; ?>>TV-PG</option>
                                        <option value="TV-14" <?php echo $content['rating'] === 'TV-14' ? 'selected' : ''; ?>>TV-14</option>
                                        <option value="TV-MA" <?php echo $content['rating'] === 'TV-MA' ? 'selected' : ''; ?>>TV-MA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="imdb_rating" class="form-label">Calificación IMDb</label>
                            <input type="number" class="form-control" id="imdb_rating" name="imdb_rating" 
                                   min="0" max="10" step="0.1" value="<?php echo $content['imdb_rating']; ?>">
                        </div>
                        
                        <!-- Video URL y Plataforma -->
                        <div class="mb-3">
                            <label for="video_platform" class="form-label">Plataforma de Video</label>
                            <select class="form-select" id="video_platform" name="video_platform" onchange="updateVideoExample()">
                                <option value="direct" <?php echo $content['video_platform'] === 'direct' ? 'selected' : ''; ?>>Archivo Directo</option>
                                <option value="youtube" <?php echo $content['video_platform'] === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                <option value="vimeo" <?php echo $content['video_platform'] === 'vimeo' ? 'selected' : ''; ?>>Vimeo</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video_url" class="form-label">URL del Video</label>
                            <input type="url" class="form-control" id="video_url" name="video_url" 
                                   value="<?php echo htmlspecialchars($content['video_url']); ?>">
                            <div id="video_example" class="platform-example"></div>
                        </div>
                        
                        <!-- Opciones adicionales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                           <?php echo $content['is_featured'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">
                                        Contenido Destacado
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_trending" name="is_trending" 
                                           <?php echo $content['is_trending'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_trending">
                                        En Tendencia
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="thumbnail" class="form-label">Portada</label>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Deja vacío para mantener la imagen actual</small>
                            
                            <?php if (!empty($content['thumbnail']) && file_exists($content['thumbnail'])): ?>
                                <div class="mt-2">
                                    <p class="text-muted">Imagen actual:</p>
                                    <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" 
                                         alt="Portada actual" class="current-thumbnail">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn-admin">
                        <i class="fas fa-save"></i> Actualizar Contenido
                    </button>
                    <a href="admin-content.php" class="btn-admin-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateVideoExample() {
            const platform = document.getElementById('video_platform').value;
            const exampleDiv = document.getElementById('video_example');
            
            switch(platform) {
                case 'youtube':
                    exampleDiv.innerHTML = '<strong>Ejemplo:</strong> https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                    break;
                case 'vimeo':
                    exampleDiv.innerHTML = '<strong>Ejemplo:</strong> https://vimeo.com/123456789';
                    break;
                case 'direct':
                    exampleDiv.innerHTML = '<strong>Ejemplo:</strong> https://ejemplo.com/video.mp4';
                    break;
                default:
                    exampleDiv.innerHTML = '';
            }
        }
        
        // Inicializar ejemplo al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            updateVideoExample();
        });
    </script>
</body>
</html>
