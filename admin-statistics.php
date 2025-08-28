<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar autenticación
requireLogin();

try {
    $conn = getConnection();
    
    // Estadísticas generales
    $stats = [];
    
    // Total usuarios
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['total_users'] = 0;
    }
    
    // Total contenido
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM content");
        $stats['total_content'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['total_content'] = 0;
    }
    
    // Contenido por tipo
    try {
        $stmt = $conn->query("SELECT type, COUNT(*) as count FROM content GROUP BY type");
        $contentByType = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $contentByType = [];
    }
    
    // Usuarios registrados por mes (últimos 6 meses)
    try {
        $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                             FROM users 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                             GROUP BY month 
                             ORDER BY month DESC");
        $usersByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $usersByMonth = [];
    }
    
    // Top contenido más popular (si existe columna view_count)
    try {
        $stmt = $conn->query("SELECT title, view_count FROM content WHERE view_count > 0 ORDER BY view_count DESC LIMIT 10");
        $topContent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $topContent = [];
    }
    
} catch (Exception $e) {
    error_log("Error en admin statistics: " . $e->getMessage());
    $stats = ['total_users' => 0, 'total_content' => 0];
    $contentByType = [];
    $usersByMonth = [];
    $topContent = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Applied macOS-style design with glassmorphism effects */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #1d1d1f;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand, .nav-link {
            color: #1d1d1f !important;
            font-weight: 500;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link {
            border-radius: 12px;
            margin: 0.2rem 1rem;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.3) !important;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .bg-primary {
            background: linear-gradient(135deg, #007AFF, #5856D6) !important;
            box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
        }
        
        .bg-success {
            background: linear-gradient(135deg, #34C759, #30D158) !important;
            box-shadow: 0 4px 15px rgba(52, 199, 89, 0.3);
        }
        
        .bg-warning {
            background: linear-gradient(135deg, #FF9500, #FFCC02) !important;
            box-shadow: 0 4px 15px rgba(255, 149, 0, 0.3);
        }
        
        .bg-info {
            background: linear-gradient(135deg, #5AC8FA, #007AFF) !important;
            box-shadow: 0 4px 15px rgba(90, 200, 250, 0.3);
        }
        
        .table-dark {
            background: transparent !important;
            color: #1d1d1f !important;
        }
        
        .table-dark th {
            background: rgba(255, 255, 255, 0.2) !important;
            border: none !important;
            color: #1d1d1f !important;
            font-weight: 600;
        }
        
        .table-dark td {
            background: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #1d1d1f !important;
        }
        
        .badge {
            border-radius: 20px !important;
            padding: 0.4rem 0.8rem !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bg-primary.badge {
            background: linear-gradient(135deg, #007AFF, #5856D6) !important;
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
        }
    </style>
</head>
<body class="bg-dark text-white">
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <i class="fas fa-crown"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Volver al sitio
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-secondary sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin-content.php">
                                <i class="fas fa-film"></i> Gestionar Contenido
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin-add-content.php">
                                <i class="fas fa-plus"></i> Agregar Contenido
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin-users.php">
                                <i class="fas fa-users"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="admin-statistics.php">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Estadísticas del Sistema</h1>
                </div>

                <!-- Estadísticas generales -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_users']; ?></h4>
                                        <p class="mb-0">Total Usuarios</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_content']; ?></h4>
                                        <p class="mb-0">Total Contenido</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-film fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count(array_filter($contentByType, function($item) { return $item['type'] === 'movie'; })); ?></h4>
                                        <p class="mb-0">Películas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-video fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count(array_filter($contentByType, function($item) { return $item['type'] === 'series'; })); ?></h4>
                                        <p class="mb-0">Series</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tv fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card bg-secondary">
                            <div class="card-header">
                                <h5 class="mb-0">Contenido por Tipo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($contentByType)): ?>
                                    <canvas id="contentTypeChart" width="400" height="200"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No hay datos disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card bg-secondary">
                            <div class="card-header">
                                <h5 class="mb-0">Registros por Mes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($usersByMonth)): ?>
                                    <canvas id="usersChart" width="400" height="200"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No hay datos disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top contenido -->
                <?php if (!empty($topContent)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-secondary">
                            <div class="card-header">
                                <h5 class="mb-0">Contenido Más Popular</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Visualizaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topContent as $content): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($content['title']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo number_format($content['view_count']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($contentByType)): ?>
    <script>
        // Gráfico de contenido por tipo
        const ctx1 = document.getElementById('contentTypeChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($contentByType, 'type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($contentByType, 'count')); ?>,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <?php if (!empty($usersByMonth)): ?>
    <script>
        // Gráfico de usuarios por mes
        const ctx2 = document.getElementById('usersChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($usersByMonth, 'month')); ?>,
                datasets: [{
                    label: 'Nuevos Usuarios',
                    data: <?php echo json_encode(array_column($usersByMonth, 'count')); ?>,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'white'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'white'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
