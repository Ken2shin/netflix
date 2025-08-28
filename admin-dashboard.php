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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #1d1d1f;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Added macOS-style design with glassmorphism effects */
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #007AFF;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: #1d1d1f;
            font-weight: 500;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
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
            <li><a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin-content.php"><i class="fas fa-film"></i> Gestionar Contenido</a></li>
            <li><a href="admin-add-content.php"><i class="fas fa-plus"></i> Agregar Contenido</a></li>
            <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a href="admin-messaging.php"><i class="fas fa-envelope"></i> Mensajería</a></li>
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
                            <a href="admin-messaging.php" class="btn-admin-secondary">
                                <i class="fas fa-envelope"></i> Enviar Mensaje a Usuarios
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
