<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$profiles = [];

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $profiles = $stmt->fetchAll();
    
    if (empty($profiles)) {
        // Si no hay perfiles, crear uno por defecto
        $stmt = $pdo->prepare("INSERT INTO profiles (user_id, name, avatar) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_name'] ?? 'Usuario', 'avatar1.png']);
        
        // Recargar perfiles
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $profiles = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = 'Error cargando perfiles: ' . $e->getMessage();
    error_log("Error en profiles.php: " . $e->getMessage());
}

// Manejar selección de perfil
if (isset($_GET['profile_id'])) {
    $profileId = (int)$_GET['profile_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = ? AND user_id = ?");
        $stmt->execute([$profileId, $_SESSION['user_id']]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            $_SESSION['profile_id'] = $profile['id'];
            $_SESSION['profile_name'] = $profile['name'];
            $_SESSION['profile_avatar'] = $profile['avatar'];
            redirect('dashboard.php');
        } else {
            $error = 'Perfil no encontrado';
        }
    } catch (Exception $e) {
        $error = 'Error seleccionando perfil: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Quién está viendo? - Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #141414;
            color: #fff;
            overflow-x: hidden;
        }

        .netflix-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
            animation: backgroundMove 25s ease-in-out infinite;
        }

        .netflix-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                135deg,
                rgba(20, 20, 20, 0.9) 0%,
                rgba(20, 20, 20, 0.6) 50%,
                rgba(20, 20, 20, 0.9) 100%
            );
            z-index: 1;
        }

        @keyframes backgroundMove {
            0%, 100% { transform: scale(1.0) translateX(0px) translateY(0px); }
            25% { transform: scale(1.05) translateX(-20px) translateY(-10px); }
            50% { transform: scale(1.1) translateX(20px) translateY(-20px); }
            75% { transform: scale(1.05) translateX(-10px) translateY(10px); }
        }

        .profiles-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .profiles-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profiles-header h1 {
            font-size: 3.5vw;
            font-weight: 400;
            color: #fff;
            margin-bottom: 20px;
        }

        .profiles-list {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 1000px;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.3s ease;
            text-decoration: none;
            color: #808080;
        }

        .profile-item:hover {
            transform: scale(1.1);
            color: #fff;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #e50914, #b20710);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid transparent;
            transition: border-color 0.3s ease;
        }

        .profile-item:hover .profile-avatar {
            border-color: #fff;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 1.3vw;
            font-weight: 400;
            text-align: center;
            max-width: 150px;
            word-wrap: break-word;
        }

        .add-profile {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
        }

        .add-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .add-icon {
            font-size: 60px;
            color: rgba(255, 255, 255, 0.6);
        }

        .error-message {
            background: rgba(229, 9, 20, 0.9);
            color: #fff;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: center;
            max-width: 500px;
        }

        .manage-profiles {
            margin-top: 40px;
        }

        .manage-profiles a {
            color: #808080;
            text-decoration: none;
            font-size: 1.2vw;
            border: 1px solid #808080;
            padding: 10px 30px;
            transition: all 0.3s ease;
        }

        .manage-profiles a:hover {
            color: #fff;
            border-color: #fff;
        }

        @media (max-width: 768px) {
            .profiles-header h1 {
                font-size: 28px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
            
            .profile-name {
                font-size: 16px;
            }
            
            .manage-profiles a {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="netflix-background"></div>
    
    <div class="profiles-container">
        <div class="profiles-header">
            <h1>¿Quién está viendo?</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profiles-list">
            <?php foreach ($profiles as $profile): ?>
                <a href="?profile_id=<?php echo $profile['id']; ?>" class="profile-item">
                    <div class="profile-avatar">
                        <img src="assets/images/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" 
                             alt="<?php echo htmlspecialchars($profile['name']); ?>">
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></div>
                </a>
            <?php endforeach; ?>
            
            <?php if (count($profiles) < MAX_PROFILES_PER_USER): ?>
                <a href="add-profile.php" class="profile-item">
                    <div class="profile-avatar add-profile">
                        <div class="add-icon">+</div>
                    </div>
                    <div class="profile-name">Agregar perfil</div>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="manage-profiles">
            <a href="manage-profiles.php">Administrar perfiles</a>
        </div>
    </div>
</body>
</html>
