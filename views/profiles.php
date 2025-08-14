<?php
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';

requireAuth();

$authController = new AuthController();
$profiles = $authController->getProfiles();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfiles - Netflix</title>
    <link rel="stylesheet" href="/assets/css/netflix-profiles.css">
</head>
<body>
    <div class="profiles-container">
        <div class="profiles-header">
            <img src="/assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
        </div>
        
        <div class="profiles-content">
            <h1>¿Quién está viendo ahora?</h1>
            
            <div class="profiles-grid">
                <?php foreach ($profiles as $profile): ?>
                    <div class="profile-item" onclick="selectProfile(<?php echo $profile['id']; ?>)">
                        <div class="profile-avatar">
                            <img src="/assets/images/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php if ($profile['is_kids']): ?>
                                <div class="kids-badge">NIÑOS</div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($profiles) < MAX_PROFILES_PER_USER): ?>
                    <div class="profile-item add-profile" onclick="window.location.href='/add-profile'">
                        <div class="profile-avatar add-avatar">
                            <div class="add-icon">+</div>
                        </div>
                        <div class="profile-name">Agregar perfil</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="profiles-actions">
                <button class="btn-secondary" onclick="window.location.href='/manage-profiles'">Administrar perfiles</button>
            </div>
        </div>
    </div>
    
    <script>
        function selectProfile(profileId) {
            fetch(`/select-profile/${profileId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = '/home';
                } else {
                    alert('Error al seleccionar perfil');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
        
        // Animaciones de hover
        document.querySelectorAll('.profile-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
