<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar autenticación
requireAuth();

$userId = getCurrentUserId();
$message = '';
$error = '';

$subscription = null;

try {
    $pdo = getConnection();
    
    // Obtener datos del usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    // Obtener información de suscripción
    $stmt = $pdo->prepare("SELECT s.*, sp.name as plan_name, sp.price, sp.features 
                          FROM subscriptions s 
                          JOIN subscription_plans sp ON s.plan_id = sp.id 
                          WHERE s.user_id = ? AND s.status = 'active'");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Error en account.php: " . $e->getMessage());
    $error = 'Error al cargar la información de la cuenta';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/netflix.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .account-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
        }
        
        .account-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .account-header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .account-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .account-section h2 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 20px;
            border-bottom: 2px solid #e50914;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #999;
            font-weight: 500;
        }
        
        .info-value {
            color: white;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #e50914;
            color: white;
        }
        
        .btn-secondary {
            background: #333;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-inactive {
            background: #dc3545;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="account-container">
        <div class="account-header">
            <h1><i class="fas fa-user-circle"></i> Mi Cuenta</h1>
            <p style="color: #999;">Gestiona tu información personal y suscripción</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <!-- Información Personal -->
        <div class="account-section">
            <h2><i class="fas fa-user"></i> Información Personal</h2>
            
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Miembro desde:</span>
                <span class="info-value"><?php echo isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A'; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Acciones:</span>
                <div>
                    <a href="edit-profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Información de Suscripción -->
        <div class="account-section">
            <h2><i class="fas fa-credit-card"></i> Suscripción</h2>
            
            <?php if ($subscription): ?>
                <div class="info-row">
                    <span class="info-label">Plan:</span>
                    <span class="info-value"><?php echo htmlspecialchars($subscription['plan_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Precio:</span>
                    <span class="info-value">$<?php echo isset($subscription['price']) ? number_format($subscription['price'], 2) : '0.00'; ?>/mes</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="status-badge status-active">Activa</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Próximo pago:</span>
                    <span class="info-value"><?php echo isset($subscription['next_billing_date']) ? date('d/m/Y', strtotime($subscription['next_billing_date'])) : 'N/A'; ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Acciones:</span>
                    <div>
                        <a href="subscription-plans.php" class="btn btn-secondary">
                            <i class="fas fa-exchange-alt"></i> Cambiar Plan
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="status-badge status-inactive">Sin Suscripción</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Acciones:</span>
                    <div>
                        <a href="subscription-plans.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Suscribirse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Gestión de Perfiles -->
        <div class="account-section">
            <h2><i class="fas fa-users"></i> Perfiles</h2>
            
            <div class="info-row">
                <span class="info-label">Gestionar perfiles:</span>
                <div>
                    <a href="manage-profiles.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Administrar Perfiles
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Acciones de Cuenta -->
        <div class="account-section">
            <h2><i class="fas fa-cogs"></i> Configuración</h2>
            
            <div class="info-row">
                <span class="info-label">Cerrar sesión:</span>
                <div>
                    <a href="logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/netflix.js"></script>
</body>
</html>
