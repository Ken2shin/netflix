<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta - Netflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/netflix.css">
</head>
<body class="netflix-body">
    <?php include 'views/partials/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="account-section">
                <h1 class="account-title">Cuenta</h1>
                
                <div class="account-info">
                    <div class="membership-section">
                        <h3>Detalles de la membresía</h3>
                        <div class="account-details">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                            <p><strong>Plan:</strong> Netflix Estándar</p>
                            <p><strong>Próxima facturación:</strong> <?php echo date('d/m/Y', strtotime('+1 month')); ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h3>Configuración de perfil</h3>
                        <div class="profile-settings">
                            <a href="profiles" class="btn btn-outline-light">Administrar perfiles</a>
                            <a href="add-profile" class="btn btn-outline-light">Agregar perfil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
