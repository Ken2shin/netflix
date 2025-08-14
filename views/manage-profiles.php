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
    <title>Administrar Perfiles - StreamFlix</title>
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
            position: relative;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            margin-bottom: 15px;
            background-size: cover;
            background-position: center;
            border: 3px solid transparent;
            position: relative;
        }

        .profile-name {
            font-size: 1.3vw;
            font-weight: 400;
            color: white;
            margin-bottom: 10px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: 1px solid #666;
            background: transparent;
            color: #999;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            border-color: white;
            color: white;
        }

        .btn-danger {
            border-color: #e50914;
            color: #e50914;
        }

        .btn-danger:hover {
            background-color: #e50914;
            color: white;
        }

        .back-btn {
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

        .back-btn:hover {
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
            
            .back-btn {
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
    <img src="/placeholder.svg?height=45&width=167&text=NETFLIX" alt="Netflix" class="netflix-logo">
    
    <div class="profiles-container">
        <h1 class="profiles-title">Administrar perfiles</h1>
        
        <div class="profiles-list">
            <?php foreach ($profiles as $profile): ?>
                <div class="profile-item">
                    <div class="profile-avatar" style="background-image: url('/placeholder.svg?height=150&width=150&text=<?php echo urlencode($profile['name']); ?>')"></div>
                    <div class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></div>
                    <div class="profile-actions">
                        <a href="/edit-profile/<?php echo $profile['id']; ?>" class="btn">Editar</a>
                        <?php if (count($profiles) > 1): ?>
                            <button onclick="deleteProfile(<?php echo $profile['id']; ?>)" class="btn btn-danger">Eliminar</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="/select-profile" class="back-btn">Listo</a>
    </div>

    <script>
        function deleteProfile(profileId) {
            if (confirm('¿Estás seguro de que quieres eliminar este perfil?')) {
                fetch('/delete-profile/' + profileId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        csrf_token: '<?php echo generateCSRFToken(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error al eliminar perfil');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
            }
        }
    </script>
</body>
</html>
