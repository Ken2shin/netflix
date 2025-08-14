<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/login');
}

$authController = new AuthController();
$profiles = $authController->getProfiles();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Quién está viendo ahora? - StreamFlix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #141414;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .netflix-logo {
            width: 167px;
            height: 45px;
            margin-bottom: 50px;
        }

        .profiles-container {
            text-align: center;
            max-width: 1200px;
            width: 100%;
            padding: 0 20px;
        }

        .profiles-title {
            font-size: 3.5vw;
            font-weight: 400;
            margin-bottom: 40px;
            color: white;
        }

        .profiles-list {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 40px;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.3s ease;
            text-decoration: none;
            color: white;
        }

        .profile-item:hover {
            transform: scale(1.1);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            margin-bottom: 15px;
            background-size: cover;
            background-position: center;
            border: 3px solid transparent;
            transition: border-color 0.3s ease;
        }

        .profile-item:hover .profile-avatar {
            border-color: white;
        }

        .profile-name {
            font-size: 1.3vw;
            font-weight: 400;
            color: #999;
            transition: color 0.3s ease;
        }

        .profile-item:hover .profile-name {
            color: white;
        }

        .add-profile .profile-avatar {
            background-color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #666;
        }

        .add-profile:hover .profile-avatar {
            background-color: #555;
            color: white;
        }

        .manage-profiles {
            margin-top: 30px;
        }

        .manage-profiles-btn {
            background: transparent;
            border: 1px solid #999;
            color: #999;
            padding: 12px 30px;
            font-size: 1.1vw;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .manage-profiles-btn:hover {
            border-color: white;
            color: white;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: 1px solid #999;
            color: #999;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            border-color: white;
            color: white;
        }

        @media (max-width: 768px) {
            .profiles-title {
                font-size: 28px;
            }
            
            .profile-name {
                font-size: 16px;
            }
            
            .manage-profiles-btn {
                font-size: 14px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <a href="/logout" class="logout-btn">Cerrar Sesión</a>
    
    <img src="/placeholder.svg?height=45&width=167&text=NETFLIX" alt="Netflix" class="netflix-logo">
    
    <div class="profiles-container">
        <h1 class="profiles-title">¿Quién está viendo ahora?</h1>
        
        <div class="profiles-list">
            <?php foreach ($profiles as $profile): ?>
                <a href="/select-profile/<?php echo $profile['id']; ?>" class="profile-item">
                    <div class="profile-avatar" style="background-image: url('/placeholder.svg?height=150&width=150&text=<?php echo urlencode($profile['name']); ?>')"></div>
                    <div class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></div>
                </a>
            <?php endforeach; ?>
            
            <?php if (count($profiles) < MAX_PROFILES_PER_USER): ?>
                <a href="/add-profile" class="profile-item add-profile">
                    <div class="profile-avatar">+</div>
                    <div class="profile-name">Agregar perfil</div>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="manage-profiles">
            <a href="/manage-profiles" class="manage-profiles-btn">Administrar perfiles</a>
        </div>
    </div>
</body>
</html>
