<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAdmin();

$currentUser = getCurrentUser();

// Obtener todo el contenido
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM content ORDER BY created_at DESC");
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
        
        .content-table {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-header {
            background: #333;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h5 {
            margin: 0;
            color: white;
        }
        
        .table {
            color: white;
            margin: 0;
        }
        
        .table th {
            background: #333;
            border: none;
            color: #b3b3b3;
            font-weight: 600;
        }
        
        .table td {
            background: #1a1a1a;
            border: none;
            vertical-align: middle;
        }
        
        .btn-admin {
            background: #e50914;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
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
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-admin-secondary:hover {
            background: #555;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .content-thumbnail {
            width: 50px;
            height: 75px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .type-movie {
            background: #007bff;
            color: white;
        }
        
        .type-series {
            background: #28a745;
            color: white;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        .alert-success {
            background: #28a745;
            color: white;
        }
        
        .alert-danger {
            background: #dc3545;
            color: white;
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
                                        <?php if (!empty($item['thumbnail']) && file_exists($item['thumbnail'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 class="content-thumbnail">
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
