<?php
require_once 'config/config.php';
require_once 'config/database.php';

requireAdmin();

$currentUser = getCurrentUser();
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        $conn = getConnection();
        
        $title = trim($_POST['title'] ?? '');
        $messageText = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'info';
        $target_type = $_POST['target_type'] ?? 'all_users';
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $selected_users = json_decode($_POST['selected_users'] ?? '[]', true);
        
        if (empty($title) || empty($messageText)) {
            throw new Exception('El título y mensaje son obligatorios');
        }
        
        // Insert notification
        $stmt = $conn->prepare("INSERT INTO admin_notifications (title, message, type, target_type, created_by, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $messageText, $type, $target_type, $currentUser['id'], $expires_at]);
        
        $notification_id = $conn->lastInsertId();
        
        // Create user notifications based on target type
        if ($target_type === 'all_users') {
            $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id) SELECT id, ? FROM users WHERE is_admin = FALSE");
            $stmt->execute([$notification_id]);
        } elseif ($target_type === 'specific_users') {
            foreach ($selected_users as $user_id) {
                $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, notification_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $notification_id]);
            }
        }
        
        $message = 'Mensaje enviado exitosamente a los usuarios';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get recent notifications
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT an.*, u.name as admin_name, 
                           (SELECT COUNT(*) FROM user_notifications un WHERE un.notification_id = an.id) as total_recipients,
                           (SELECT COUNT(*) FROM user_notifications un WHERE un.notification_id = an.id AND un.is_read = TRUE) as read_count
                           FROM admin_notifications an 
                           LEFT JOIN users u ON an.created_by = u.id 
                           ORDER BY an.created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_notifications = $stmt->fetchAll();
    
    if (!$recent_notifications) {
        $recent_notifications = [];
    }
} catch (Exception $e) {
    $recent_notifications = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Mensajería - Admin</title>
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
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #007AFF, #5856D6);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.4);
            color: white;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #1d1d1f;
            backdrop-filter: blur(10px);
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.4);
            border-color: #007AFF;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 255, 0.25);
            color: #1d1d1f;
        }
        
        .alert {
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .notification-item {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .notification-item.info { border-left-color: #007AFF; }
        .notification-item.success { border-left-color: #34C759; }
        .notification-item.warning { border-left-color: #FF9500; }
        .notification-item.error { border-left-color: #FF3B30; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand" style="padding: 0 2rem; margin-bottom: 2rem;">
            <h4 style="color: #1d1d1f; font-weight: 600;"><i class="fas fa-envelope"></i> Mensajería</h4>
        </div>
        <ul class="sidebar-nav" style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 0.5rem;">
                <a href="admin-dashboard.php" style="display: flex; align-items: center; padding: 1rem 2rem; color: #1d1d1f; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-arrow-left" style="margin-right: 12px;"></i> Volver al Dashboard
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="glass-card">
            <h2 style="margin-bottom: 2rem; color: #1d1d1f; font-weight: 600;">
                <i class="fas fa-paper-plane"></i> Enviar Mensaje a Usuarios
            </h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Título del Mensaje</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="type" class="form-label fw-bold">Tipo</label>
                            <select class="form-select" id="type" name="type">
                                <option value="info">Información</option>
                                <option value="success">Éxito</option>
                                <option value="warning">Advertencia</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label fw-bold">Mensaje</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="target_type" class="form-label fw-bold">Destinatarios</label>
                            <select class="form-select" id="target_type" name="target_type">
                                <option value="all_users">Todos los usuarios</option>
                                <option value="specific_users">Usuarios específicos</option>
                                <option value="subscription_plan">Por plan de suscripción</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expires_at" class="form-label fw-bold">Fecha de Expiración (Opcional)</label>
                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                        </div>
                    </div>
                </div>
                
                <div id="userSearchContainer" class="mb-3" style="display: none;">
                    <label for="user_search" class="form-label fw-bold">Buscar Usuarios</label>
                    <input type="text" class="form-control" id="user_search" placeholder="Escribe el nombre o email del usuario...">
                    <div id="user_results" class="mt-2"></div>
                    <input type="hidden" name="selected_users" id="selected_users">
                    <div id="selected_users_display" class="mt-2"></div>
                </div>
                
                <button type="submit" class="btn-admin">
                    <i class="fas fa-paper-plane"></i> Enviar Mensaje
                </button>
            </form>
        </div>
        
        <div class="glass-card">
            <h3 style="margin-bottom: 1.5rem; color: #1d1d1f; font-weight: 600;">
                <i class="fas fa-history"></i> Mensajes Recientes
            </h3>
            
            <?php if (!empty($recent_notifications) && is_array($recent_notifications)): ?>
                <?php foreach ($recent_notifications as $notification): ?>
                    <div class="notification-item <?php echo htmlspecialchars($notification['type'] ?? 'info'); ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($notification['title'] ?? 'Sin título'); ?></h6>
                                <p class="mb-2"><?php echo htmlspecialchars($notification['message'] ?? 'Sin mensaje'); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($notification['admin_name'] ?? 'Admin'); ?> • 
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-primary"><?php echo $notification['total_recipients'] ?? 0; ?> destinatarios</div>
                                <div class="badge bg-success"><?php echo $notification['read_count'] ?? 0; ?> leídos</div>
                                <div class="badge bg-<?php echo htmlspecialchars($notification['type'] ?? 'info'); ?>"><?php echo htmlspecialchars($notification['type'] ?? 'info'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center">No hay mensajes recientes</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('target_type').addEventListener('change', function() {
            const targetType = this.value;
            const userSearchContainer = document.getElementById('userSearchContainer');
            
            if (targetType === 'specific_users') {
                if (!userSearchContainer) {
                    const searchDiv = document.createElement('div');
                    searchDiv.id = 'userSearchContainer';
                    searchDiv.className = 'mb-3';
                    searchDiv.innerHTML = `
                        <label for="user_search" class="form-label fw-bold">Buscar Usuarios</label>
                        <input type="text" class="form-control" id="user_search" placeholder="Escribe el nombre o email del usuario...">
                        <div id="user_results" class="mt-2"></div>
                        <input type="hidden" name="selected_users" id="selected_users">
                        <div id="selected_users_display" class="mt-2"></div>
                    `;
                    this.closest('.mb-3').after(searchDiv);
                    
                    // Add search functionality
                    document.getElementById('user_search').addEventListener('input', searchUsers);
                }
                userSearchContainer.style.display = 'block';
            } else if (userSearchContainer) {
                userSearchContainer.style.display = 'none';
                userSearchContainer.innerHTML = '';
                document.getElementById('selected_users').value = '';
            }
        });

        function searchUsers() {
            const query = document.getElementById('user_search').value;
            if (query.length < 2) {
                document.getElementById('user_results').innerHTML = '';
                return;
            }
            
            fetch('api/search-users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ query: query })
            })
            .then(response => response.json())
            .then(users => {
                const resultsDiv = document.getElementById('user_results');
                resultsDiv.innerHTML = '';
                
                users.forEach(user => {
                    const userDiv = document.createElement('div');
                    userDiv.className = 'user-result p-2 border rounded mb-1 cursor-pointer';
                    userDiv.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <span>${user.name} (${user.email})</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectUser(${user.id}, '${user.name}', '${user.email}')">
                                Seleccionar
                            </button>
                        </div>
                    `;
                    resultsDiv.appendChild(userDiv);
                });
            })
            .catch(error => console.error('Error searching users:', error));
        }

        let selectedUsers = [];

        function selectUser(id, name, email) {
            if (!selectedUsers.find(u => u.id === id)) {
                selectedUsers.push({ id, name, email });
                updateSelectedUsers();
            }
        }

        function updateSelectedUsers() {
            const selectedDiv = document.getElementById('selected_users_display') || createSelectedUsersDisplay();
            selectedDiv.innerHTML = '';
            
            selectedUsers.forEach(user => {
                const userTag = document.createElement('span');
                userTag.className = 'badge bg-primary me-2 mb-2';
                userTag.innerHTML = `
                    ${user.name} 
                    <button type="button" class="btn-close btn-close-white ms-1" onclick="removeUser(${user.id})"></button>
                `;
                selectedDiv.appendChild(userTag);
            });
            
            document.getElementById('selected_users').value = JSON.stringify(selectedUsers.map(u => u.id));
        }

        function createSelectedUsersDisplay() {
            const div = document.createElement('div');
            div.id = 'selected_users_display';
            div.className = 'mt-2';
            document.getElementById('userSearchContainer').appendChild(div);
            return div;
        }

        function removeUser(id) {
            selectedUsers = selectedUsers.filter(u => u.id !== id);
            updateSelectedUsers();
        }
    </script>
</body>
</html>
