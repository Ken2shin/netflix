<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAdmin();

$currentUser = getCurrentUser();

// Obtener estadísticas
try {
    $conn = getConnection();
    
    // Total usuarios
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Total contenido
    $stmt = $conn->query("SELECT COUNT(*) as total FROM content");
    $totalContent = $stmt->fetch()['total'];
    
    // Total películas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM content WHERE type = 'movie'");
    $totalMovies = $stmt->fetch()['total'];
    
    // Total series
    $stmt = $conn->query("SELECT COUNT(*) as total FROM content WHERE type = 'series'");
    $totalSeries = $stmt->fetch()['total'];
    
    // Contenido reciente
    $stmt = $conn->prepare("SELECT * FROM content ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentContent = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error en admin-dashboard: " . $e->getMessage());
    $totalUsers = 0;
    $totalContent = 0;
    $totalMovies = 0;
    $totalSeries = 0;
    $recentContent = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Netflix</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: #1a1a1a;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #e50914;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: #b3b3b3;
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
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
            <li><a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin-content.php"><i class="fas fa-film"></i> Gestionar Contenido</a></li>
            <li><a href="admin-add-content.php"><i class="fas fa-plus"></i> Agregar Contenido</a></li>
            <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a href="admin-statistics.php"><i class="fas fa-chart-bar"></i> Estadísticas</a></li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2>Dashboard</h2>
            <div>
                <a href="dashboard.php" class="btn-admin-secondary me-2">
                    <i class="fas fa-home"></i> Volver al sitio
                </a>
                <a href="logout.php" class="btn-admin">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Usuarios Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalContent); ?></div>
                <div class="stat-label">Total Contenido</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalMovies); ?></div>
                <div class="stat-label">Películas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalSeries); ?></div>
                <div class="stat-label">Series</div>
            </div>
        </div>
        
        <!-- Contenido Reciente -->
        <div class="content-table">
            <div class="table-header">
                <h5>Contenido Agregado Recientemente</h5>
            </div>
            <?php if (!empty($recentContent)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Portada</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Año</th>
                            <th>Fecha Agregado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentContent as $item): ?>
                            <tr>
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
                                        <?php echo $item['type'] === 'movie' ? 'Película' : 'Serie'; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['release_year'] ?? 'N/A'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <a href="admin-edit-content.php?id=<?php echo $item['id']; ?>" class="btn-admin-secondary btn-sm me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="content-details.php?id=<?php echo $item['id']; ?>" class="btn-admin btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-center">
                    <p class="text-muted">No hay contenido disponible</p>
                    <a href="admin-add-content.php" class="btn-admin mt-2">
                        <i class="fas fa-plus"></i> Agregar Contenido
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="content-table">
                    <div class="table-header">
                        <h5>Acciones Rápidas</h5>
                    </div>
                    <div class="p-4">
                        <div class="d-grid gap-2">
                            <a href="admin-add-content.php" class="btn-admin">
                                <i class="fas fa-plus"></i> Agregar Nuevo Contenido
                            </a>
                            <a href="admin-content.php" class="btn-admin-secondary">
                                <i class="fas fa-list"></i> Ver Todo el Contenido
                            </a>
                            <a href="admin-users.php" class="btn-admin-secondary">
                                <i class="fas fa-users"></i> Gestionar Usuarios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-table">
                    <div class="table-header">
                        <h5>Información del Sistema</h5>
                    </div>
                    <div class="p-4">
                        <!-- Fixed PHP errors by adding null checks -->
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($currentUser['name'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email'] ?? 'N/A'); ?></p>
                        <p><strong>Rol:</strong> Administrador</p>
                        <p><strong>Última conexión:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
