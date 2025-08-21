<?php
require_once 'config/config.php';
require_once 'models/Profile.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$profileModel = new Profile($database);

$user_id = $_SESSION['user_id'];
$profiles = $profileModel->findByUserId($user_id);

$error = '';
$success = '';

// Manejar eliminación de perfil
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $profile_id = $_GET['delete'];
    
    // Verificar que el perfil pertenece al usuario
    $profile = $profileModel->findById($profile_id);
    if ($profile && $profile['user_id'] == $user_id) {
        if ($profileModel->delete($profile_id)) {
            $success = 'Perfil eliminado correctamente';
            $profiles = $profileModel->findByUserId($user_id); // Recargar perfiles
        } else {
            $error = 'Error al eliminar el perfil';
        }
    } else {
        $error = 'Perfil no encontrado';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Perfiles - Netflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/netflix-profiles.css">
    <style>
        body {
            background: #141414;
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            background-image: url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .overlay {
            background: rgba(0, 0, 0, 0.7);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .profiles-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profiles-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .profiles-header h1 {
            font-size: 3rem;
            font-weight: 400;
            margin-bottom: 1rem;
        }
        
        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .profile-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            margin-bottom: 1rem;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .profile-info {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-edit, .btn-delete {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #e50914;
            color: white;
        }
        
        .btn-edit:hover {
            background: #f40612;
            color: white;
        }
        
        .btn-delete {
            background: #333;
            color: white;
        }
        
        .btn-delete:hover {
            background: #555;
        }
        
        .add-profile-card {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed #666;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-profile-card:hover {
            border-color: #e50914;
            background: rgba(229, 9, 20, 0.1);
        }
        
        .add-icon {
            font-size: 3rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .add-profile-card:hover .add-icon {
            color: #e50914;
        }
        
        .back-btn {
            background: #333;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin: 0 auto;
            display: block;
            width: fit-content;
        }
        
        .back-btn:hover {
            background: #555;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #51cf66;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="profiles-container">
            <div class="profiles-header">
                <h1>Administrar Perfiles</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="profiles-grid">
                <?php foreach ($profiles as $profile): ?>
                    <div class="profile-card">
                        <img src="assets/images/avatars/<?php echo $profile['avatar']; ?>" 
                             alt="<?php echo htmlspecialchars($profile['name']); ?>" 
                             class="profile-avatar">
                        <div class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></div>
                        <div class="profile-info">
                            <?php echo $profile['is_kids'] ? 'Perfil para niños' : 'Perfil general'; ?>
                        </div>
                        <div class="profile-actions">
                            <a href="edit-profile.php?id=<?php echo $profile['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button onclick="confirmDelete(<?php echo $profile['id']; ?>, '<?php echo htmlspecialchars($profile['name']); ?>')" class="btn-delete">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($profiles) < MAX_PROFILES_PER_USER): ?>
                    <div class="profile-card add-profile-card" onclick="window.location.href='add-profile.php'">
                        <i class="fas fa-plus add-icon"></i>
                        <div class="profile-name">Agregar Perfil</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <a href="profiles.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver a Perfiles
            </a>
        </div>
    </div>
    
    <script>
        function confirmDelete(profileId, profileName) {
            if (confirm(`¿Estás seguro de que quieres eliminar el perfil "${profileName}"?`)) {
                window.location.href = `manage-profiles.php?delete=${profileId}`;
            }
        }
    </script>
</body>
</html>
