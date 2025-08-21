<?php
require_once 'config/config.php';
require_once 'models/Profile.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$profileModel = new Profile($database);

$user_id = $_SESSION['user_id'];
$profiles = $profileModel->findByUserId($user_id);

// Verificar límite de perfiles
if (count($profiles) >= MAX_PROFILES_PER_USER) {
    header('Location: manage-profiles.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $avatar = $_POST['avatar'] ?? 'avatar1.png';
    $is_kids = isset($_POST['is_kids']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'El nombre del perfil es obligatorio';
    } elseif (strlen($name) > 50) {
        $error = 'El nombre no puede tener más de 50 caracteres';
    } else {
        // Verificar que no existe otro perfil con el mismo nombre para este usuario
        $existing = $profileModel->findByUserIdAndName($user_id, $name);
        if ($existing) {
            $error = 'Ya existe un perfil con ese nombre';
        } else {
            if ($profileModel->create($user_id, $name, $avatar, $is_kids)) {
                $success = 'Perfil creado correctamente';
            } else {
                $error = 'Error al crear el perfil';
            }
        }
    }
}

$avatars = [
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
    <title>Agregar Perfil - Netflix</title>
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
        
        .add-profile-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-header h1 {
            font-size: 3rem;
            font-weight: 400;
            margin-bottom: 1rem;
        }
        
        .add-form {
            background: rgba(0, 0, 0, 0.8);
            padding: 2rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .avatar-selection {
            margin-bottom: 2rem;
        }
        
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .avatar-option {
            position: relative;
            cursor: pointer;
        }
        
        .avatar-option input[type="radio"] {
            display: none;
        }
        
        .avatar-option img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .avatar-option input[type="radio"]:checked + img {
            border-color: #e50914;
            transform: scale(1.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            background: #333;
            border: 1px solid #555;
            color: white;
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            background: #333;
            border-color: #e50914;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .form-check {
            margin-bottom: 1rem;
        }
        
        .form-check-input {
            background-color: #333;
            border-color: #555;
        }
        
        .form-check-input:checked {
            background-color: #e50914;
            border-color: #e50914;
        }
        
        .btn-netflix {
            background: #e50914;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-netflix:hover {
            background: #f40612;
        }
        
        .btn-secondary {
            background: #333;
            color: white;
            border: 1px solid #555;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #555;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
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
        
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="add-profile-container">
            <div class="profile-header">
                <h1>Agregar Perfil</h1>
            </div>
            
            <div class="add-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <br><br>
                        <a href="manage-profiles.php" class="btn-netflix">Ver Perfiles</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="avatar-selection">
                            <label>Selecciona un avatar:</label>
                            <div class="avatar-grid">
                                <?php foreach ($avatars as $avatar_file => $avatar_name): ?>
                                    <div class="avatar-option">
                                        <input type="radio" 
                                               name="avatar" 
                                               value="<?php echo $avatar_file; ?>" 
                                               id="avatar_<?php echo $avatar_file; ?>"
                                               <?php echo $avatar_file === 'avatar1.png' ? 'checked' : ''; ?>>
                                        <img src="assets/images/avatars/<?php echo $avatar_file; ?>" 
                                             alt="<?php echo $avatar_name; ?>"
                                             onclick="document.getElementById('avatar_<?php echo $avatar_file; ?>').checked = true;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Nombre del perfil:</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   required 
                                   maxlength="50"
                                   placeholder="Ingresa el nombre del perfil">
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_kids" 
                                   name="is_kids">
                            <label class="form-check-label" for="is_kids">
                                Perfil para niños
                            </label>
                            <small class="form-text text-muted">
                                Los perfiles para niños solo muestran contenido apropiado para menores de edad.
                            </small>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn-netflix">
                                <i class="fas fa-plus"></i> Crear Perfil
                            </button>
                            <a href="manage-profiles.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
