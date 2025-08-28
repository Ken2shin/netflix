<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAdmin();

$currentUser = getCurrentUser();

// Obtener todo el contenido
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, title, type, release_year, rating, poster_url, thumbnail, imdb_id FROM content ORDER BY created_at DESC");
    $stmt->execute();
    $allContent = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error en admin-content: " . $e->getMessage());
    $allContent = [];
}

// Procesar eliminación
if (isset($_POST['delete_content'])) {
    $contentId = (int)$_POST['content_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM content WHERE id = ?");
        if ($stmt->execute([$contentId])) {
            $success = "Contenido eliminado exitosamente";
            // Recargar la página para actualizar la lista
            header("Location: admin-content.php?success=" . urlencode($success));
            exit;
        } else {
            $error = "Error al eliminar el contenido";
        }
    } catch (Exception $e) {
        error_log("Error eliminando contenido: " . $e->getMessage());
        $error = "Error al eliminar el contenido";
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Contenido - Netflix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #1d1d1f;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Applied macOS-style design with glassmorphism effects */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            padding: 2rem 0;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-brand {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-brand h4 {
            color: #1d1d1f;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #1d1d1f;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .sidebar-nav a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.3);
            color: #1d1d1f;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 1.5rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .top-bar h2 {
            margin: 0;
            color: #1d1d1f;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .content-table {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            background: rgba(255, 255, 255, 0.3);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h5 {
            margin: 0;
            color: #1d1d1f;
            font-weight: 600;
            font-size: 1.3rem;
        }
        
        .table {
            color: #1d1d1f;
            margin: 0;
        }
        
        .table th {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #1d1d1f;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table td {
            background: transparent;
            border: none;
            vertical-align: middle;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #007AFF, #5856D6);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.4);
            color: white;
        }
        
        .btn-admin-secondary {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #1d1d1f;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-admin-secondary:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            color: #1d1d1f;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #FF3B30, #FF6B6B);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(255, 59, 48, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 59, 48, 0.4);
        }
        
        .content-thumbnail {
            width: 50px;
            height: 75px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .type-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .type-movie {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        .type-series {
            background: linear-gradient(135deg, #4ECDC4, #44A08D);
            color: white;
            box-shadow: 0 2px 8px rgba(78, 205, 196, 0.3);
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert-success {
            background: rgba(52, 199, 89, 0.2);
            border: 1px solid rgba(52, 199, 89, 0.3);
            color: #1d1d1f;
        }
        
        .alert-danger {
            background: rgba(255, 59, 48, 0.2);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #1d1d1f;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
            <li><a href="admin-content.php" class="active"><i class="fas fa-film"></i> Gestionar Contenido</a></li>
            <li><a href="admin-add-content.php"><i class="fas fa-plus"></i> Agregar Contenido</a></li>
            <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a href="admin-statistics.php"><i class="fas fa-chart-bar"></i> Estadísticas</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2>Gestionar Contenido</h2>
            <div>
                <a href="admin-add-content.php" class="btn-admin me-2">
                    <i class="fas fa-plus"></i> Agregar Contenido
                </a>
                <a href="dashboard.php" class="btn-admin-secondary me-2">
                    <i class="fas fa-home"></i> Volver al sitio
                </a>
                <a href="logout.php" class="btn-admin">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
        
        <!-- Mensajes -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Tabla de Contenido -->
        <div class="content-table">
            <div class="table-header">
                <h5>Todo el Contenido (<?php echo count($allContent); ?>)</h5>
            </div>
            <?php if (!empty($allContent)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Portada</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Género</th>
                                <th>Año</th>
                                <th>Rating</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allContent as $item): ?>
                                <tr>
                                    <td><?php echo $item['id']; ?></td>
                                    <td>
                                        <?php 
                                        require_once 'includes/image-handler.php';
                                        $poster_path = ImageHandler::forceDisplayPoster($item, 'small');
                                        
                                        if (!empty($poster_path) && !strpos($poster_path, 'placeholder.svg')): ?>
                                            <img src="<?php echo htmlspecialchars($poster_path); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 class="content-thumbnail"
                                                 onerror="this.parentElement.innerHTML='<div class=\'content-thumbnail bg-secondary d-flex align-items-center justify-content-center\'><i class=\'fas fa-film\'></i></div>'">
                                        <?php else: ?>
                                            <div class="content-thumbnail bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fas fa-film"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td>
                                        <span class="type-badge <?php echo $item['type'] === 'movie' ? 'type-movie' : 'type-series'; ?>">
                                            <?php echo $item['type'] === 'movie' ? 'movie' : 'series'; ?>
                                        </span>
                                    </td>
                                    <td>N/A</td>
                                    <td><?php echo $item['release_year'] ?? 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($item['rating'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="admin-edit-content.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-warning btn-sm me-1" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title']); ?>')"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <p class="text-muted">No hay contenido disponible</p>
                    <a href="admin-add-content.php" class="btn-admin mt-2">
                        <i class="fas fa-plus"></i> Agregar Contenido
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-white">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white">
                    <p>¿Estás seguro de que quieres eliminar el contenido "<span id="contentTitle"></span>"?</p>
                    <p class="text-warning"><small>Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="content_id" id="deleteContentId">
                        <button type="submit" name="delete_content" class="btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(contentId, contentTitle) {
            document.getElementById('contentTitle').textContent = contentTitle;
            document.getElementById('deleteContentId').value = contentId;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
