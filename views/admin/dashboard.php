<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - StreamFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'views/admin/partials/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'views/admin/partials/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Resumen general de la plataforma</p>
                    </div>
                </div>
                
                <!-- Estadísticas principales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-number"><?php echo number_format($data['stats']['total_users']); ?></div>
                                <div class="stats-label">Usuarios Activos</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon bg-success">
                                <i class="fas fa-film"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-number"><?php echo number_format($data['stats']['total_content']); ?></div>
                                <div class="stats-label">Total Contenido</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon bg-warning">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-number"><?php echo number_format($data['stats']['views_today']); ?></div>
                                <div class="stats-label">Visualizaciones Hoy</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon bg-info">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-number"><?php echo number_format($data['stats']['total_profiles']); ?></div>
                                <div class="stats-label">Perfiles Creados</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Contenido más visto -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Contenido Más Visto</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Tipo</th>
                                                <th>Año</th>
                                                <th>Visualizaciones</th>
                                                <th>Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($data['topContent'] as $content): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="uploads/thumbnails/<?php echo $content['thumbnail']; ?>" 
                                                                 alt="<?php echo htmlspecialchars($content['title']); ?>"
                                                                 class="content-thumbnail me-3">
                                                            <strong><?php echo htmlspecialchars($content['title']); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $content['type'] === 'movie' ? 'primary' : 'success'; ?>">
                                                            <?php echo $content['type'] === 'movie' ? 'Película' : 'Serie'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $content['release_year']; ?></td>
                                                    <td><?php echo number_format($content['total_views']); ?></td>
                                                    <td>
                                                        <div class="rating">
                                                            <i class="fas fa-star text-warning"></i>
                                                            <?php echo $content['imdb_rating']; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usuarios recientes -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Usuarios Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach($data['recentUsers'] as $user): ?>
                                    <div class="user-item">
                                        <div class="user-info">
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <div class="user-meta">
                                                <span class="badge bg-<?php echo $user['subscription_type'] === 'premium' ? 'warning' : ($user['subscription_type'] === 'standard' ? 'info' : 'secondary'); ?>">
                                                    <?php echo ucfirst($user['subscription_type']); ?>
                                                </span>
                                                <small class="text-muted ms-2">
                                                    <?php echo $user['profile_count']; ?> perfiles
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                Registrado: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos adicionales -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Distribución de Contenido</h5>
                            </div>
                            <div class="card-body">
                                <div class="content-distribution">
                                    <div class="distribution-item">
                                        <div class="distribution-label">Películas</div>
                                        <div class="distribution-bar">
                                            <div class="distribution-fill bg-primary" 
                                                 style="width: <?php echo ($data['stats']['total_movies'] / $data['stats']['total_content']) * 100; ?>%"></div>
                                        </div>
                                        <div class="distribution-value"><?php echo $data['stats']['total_movies']; ?></div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-label">Series</div>
                                        <div class="distribution-bar">
                                            <div class="distribution-fill bg-success" 
                                                 style="width: <?php echo ($data['stats']['total_series'] / $data['stats']['total_content']) * 100; ?>%"></div>
                                        </div>
                                        <div class="distribution-value"><?php echo $data['stats']['total_series']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="admin-add-content.php" class="btn btn-primary btn-lg mb-3 w-100">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar Contenido
                                    </a>
                                    <a href="admin-users.php" class="btn btn-outline-primary btn-lg mb-3 w-100">
                                        <i class="fas fa-users me-2"></i>
                                        Gestionar Usuarios
                                    </a>
                                    <a href="admin-statistics.php" class="btn btn-outline-success btn-lg w-100">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Ver Estadísticas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
