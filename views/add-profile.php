<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/login');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $authController->addProfile();
}

$availableAvatars = [
    'avatar1.png' => 'Avatar 1',
    'avatar2.png' => 'Avatar 2',
    'avatar3.png' => 'Avatar 3',
    'avatar4.png' => 'Avatar 4',
    'avatar5.png' => 'Avatar 5'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Perfil - StreamFlix</title>
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
            padding: 20px;
        }

        .netflix-logo {
            width: 167px;
            height: 45px;
            margin-bottom: 50px;
        }

        .add-profile-container {
            background-color: #333;
            padding: 60px;
            border-radius: 4px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        .add-profile-title {
            font-size: 64px;
            font-weight: 400;
            margin-bottom: 20px;
        }

        .add-profile-subtitle {
            font-size: 18px;
            color: #999;
            margin-bottom: 40px;
        }

        .avatar-selection {
            margin-bottom: 30px;
        }

        .avatar-selection h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: white;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            justify-items: center;
        }

        .avatar-option {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s ease;
            border: 3px solid transparent;
            background-size: cover;
            background-position: center;
        }

        .avatar-option:hover,
        .avatar-option.selected {
            transform: scale(1.1);
            border-color: white;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            background-color: #555;
            border: 1px solid #666;
            color: white;
            border-radius: 4px;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: white;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            text-align: left;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.5);
        }

        .checkbox-group label {
            font-size: 16px;
            cursor: pointer;
        }

        .kids-info {
            font-size: 14px;
            color: #999;
            margin-top: 10px;
        }

        .form-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 15px 30px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background-color: #e50914;
            color: white;
        }

        .btn-primary:hover {
            background-color: #f40612;
        }

        .btn-secondary {
            background-color: transparent;
            color: #999;
            border: 1px solid #666;
        }

        .btn-secondary:hover {
            color: white;
            border-color: white;
        }

        .error {
            background-color: #e50914;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .add-profile-container {
                padding: 40px 20px;
            }
            
            .add-profile-title {
                font-size: 48px;
            }
            
            .avatar-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <img src="/placeholder.svg?height=45&width=167&text=NETFLIX" alt="Netflix" class="netflix-logo">
    
    <div class="add-profile-container">
        <h1 class="add-profile-title">Agregar perfil</h1>
        <p class="add-profile-subtitle">Agrega un perfil para otra persona que vea Netflix.</p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/add-profile">
            <div class="avatar-selection">
                <h3>Selecciona un avatar:</h3>
                <div class="avatar-grid">
                    <?php foreach ($availableAvatars as $filename => $name): ?>
                        <div class="avatar-option" 
                             data-avatar="<?php echo $filename; ?>"
                             style="background-image: url('/placeholder.svg?height=80&width=80&text=<?php echo urlencode($name); ?>')">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <input type="text" name="name" placeholder="Nombre" required maxlength="50">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="is_kids" id="is_kids">
                <label for="is_kids">¿Perfil para niños?</label>
            </div>
            <div class="kids-info">
                Los perfiles para niños solo muestran series y películas apropiadas para menores de 12 años.
            </div>
            
            <input type="hidden" name="avatar" value="avatar1.png">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Continuar</button>
                <a href="/select-profile" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        // Manejar selección de avatar
        document.querySelectorAll('.avatar-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remover selección anterior
                document.querySelectorAll('.avatar-option').forEach(opt => opt.classList.remove('selected'));
                
                // Agregar selección actual
                this.classList.add('selected');
                
                // Actualizar campo hidden
                const avatar = this.dataset.avatar;
                document.querySelector('input[name="avatar"]').value = avatar;
            });
        });
        
        // Seleccionar primer avatar por defecto
        document.querySelector('.avatar-option').classList.add('selected');
    </script>
</body>
</html>
