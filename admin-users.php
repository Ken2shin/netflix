<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar autenticación
requireLogin();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $conn = getConnection();
        $action = $_POST['action'];
        $userId = (int)$_POST['user_id'];
        
        if ($action === 'get_user_details') {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Obtener estadísticas adicionales del usuario
                $stats = [];
                
                // Contar perfiles
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM profiles WHERE user_id = ?");
                $stmt->execute([$userId]);
                $stats['profiles'] = $stmt->fetch()['count'] ?? 0;
                
                // Contar visualizaciones (si existe la tabla)
                try {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM viewing_history vh 
                                          JOIN profiles p ON vh.profile_id = p.id 
                                          WHERE p.user_id = ?");
                    $stmt->execute([$userId]);
                    $stats['views'] = $stmt->fetch()['count'] ?? 0;
                } catch (Exception $e) {
                    $stats['views'] = 0;
                }
                
                // Último acceso
                $stats['last_login'] = $user['last_login'] ?? 'Nunca';
                
                echo json_encode([
                    'success' => true,
                    'user' => $user,
                    'stats' => $stats
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            }
            exit();
        }
        
        if ($action === 'toggle_admin') {
            $stmt = $conn->prepare("UPDATE users SET is_admin = NOT COALESCE(is_admin, 0) WHERE id = ?");
            if ($stmt->execute([$userId])) {
                echo json_encode(['success' => true, 'message' => 'Permisos actualizados']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar permisos']);
            }
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

try {
    $conn = getConnection();
    
    // Obtener usuarios con paginación
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de usuarios para paginación
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'] ?? 0;
    $totalPages = ceil($totalUsers / $limit);
    
} catch (Exception $e) {
    error_log("Error en admin users: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 1;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        .btn-info {
            background: linear-gradient(135deg, #007AFF, #5856D6) !important;
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-info:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 122, 255, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FF9500, #FFCC02) !important;
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 8px rgba(255, 149, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 149, 0, 0.4);
        }
        
        .badge {
            border-radius: 20px !important;
            padding: 0.4rem 0.8rem !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bg-warning {
            background: linear-gradient(135deg, #FF9500, #FFCC02) !important;
            box-shadow: 0 2px 8px rgba(255, 149, 0, 0.3);
        }
        
        .bg-secondary {
            background: rgba(142, 142, 147, 0.3) !important;
            backdrop-filter: blur(10px);
        }
        
        .bg-info {
            background: linear-gradient(135deg, #007AFF, #5856D6) !important;
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #1d1d1f !important;
        }
        
        .pagination .page-link {
            background: rgba(255, 255, 255, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #1d1d1f !important;
            border-radius: 8px !important;
            margin: 0 2px;
            transition: all 0.3s ease;
        }
        
        .pagination .page-link:hover {
            background: rgba(255, 255, 255, 0.4) !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #007AFF, #5856D6) !important;
            border-color: transparent !important;
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
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
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
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
                            <a class="nav-link active text-white" href="admin-users.php">
                                <i class="fas fa-users"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="admin-statistics.php">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2">Gestionar Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-info fs-6">Total: <?php echo $totalUsers; ?></span>
                        </div>
                    </div>
                </div>

                <?php if (empty($users)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay usuarios registrados en el sistema.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-dark">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Email</th>
                                            <th>Admin</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (isset($user['is_admin']) && $user['is_admin']): ?>
                                                    <span class="badge bg-warning">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Usuario</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (isset($user['created_at'])) {
                                                    echo date('d/m/Y H:i', strtotime($user['created_at']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (!isset($user['is_admin']) || !$user['is_admin']): ?>
                                                <button class="btn btn-sm btn-warning" onclick="toggleAdmin(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-user-shield"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($totalPages > 1): ?>
                            <nav aria-label="Paginación de usuarios">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal de detalles de usuario -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUserDetails(userId) {
            const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            const content = document.getElementById('userDetailsContent');
            
            // Mostrar loading
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Obtener detalles del usuario
            fetch('admin-users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_user_details&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    const stats = data.stats;
                    
                    content.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Información Personal</h6>
                                <table class="table table-dark table-sm">
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td>${user.id}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>${user.email || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipo:</strong></td>
                                        <td>${user.is_admin ? '<span class="badge bg-warning">Administrador</span>' : '<span class="badge bg-secondary">Usuario</span>'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Registro:</strong></td>
                                        <td>${user.created_at ? new Date(user.created_at).toLocaleString('es-ES') : 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Último acceso:</strong></td>
                                        <td>${stats.last_login !== 'Nunca' ? new Date(stats.last_login).toLocaleString('es-ES') : 'Nunca'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Estadísticas</h6>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="card bg-secondary">
                                            <div class="card-body">
                                                <h3 class="text-info">${stats.profiles}</h3>
                                                <small>Perfiles</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-secondary">
                                            <div class="card-body">
                                                <h3 class="text-success">${stats.views}</h3>
                                                <small>Visualizaciones</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar los detalles del usuario
                    </div>
                `;
            });
        }

        function toggleAdmin(userId) {
            if (confirm('¿Convertir este usuario en administrador?')) {
                fetch('admin-users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_admin&user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Permisos actualizados correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar permisos');
                });
            }
        }
    </script>
</body>
</html>
