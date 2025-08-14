<?php
session_start();

// Si no hay usuario logueado, mostrar landing page
if (!isset($_SESSION['user_id'])) {
    include 'views/landing.php';
    exit;
}

// Si no hay perfil seleccionado, ir a selección de perfiles
if (!isset($_SESSION['profile_id'])) {
    header('Location: profiles.php');
    exit;
}

// Mostrar dashboard principal
include 'views/dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #141414;
            color: white;
            min-height: 100vh;
        }

        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
            z-index: 1000;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            height: 25px;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            list-style: none;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #b3b3b3;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: #333;
        }

        .main-content {
            padding-top: 80px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .welcome-message {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .subtitle {
            font-size: 24px;
            color: #b3b3b3;
            margin-bottom: 40px;
        }

        .cta-button {
            background: #e50914;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .cta-button:hover {
            background: #f40612;
        }

        @media (max-width: 740px) {
            .header {
                padding: 20px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .welcome-message {
                font-size: 32px;
            }
            
            .subtitle {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <img src="assets/images/netflix-logo.png" alt="Netflix" class="logo">
        
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="series.php">Series</a></li>
                <li><a href="movies.php">Películas</a></li>
                <li><a href="my-list.php">Mi lista</a></li>
            </ul>
        </nav>
        
        <div class="user-menu">
            <img src="assets/images/avatars/avatar1.png" alt="Perfil" class="profile-avatar">
            <a href="profiles.php" style="color: white; text-decoration: none;">Cambiar perfil</a>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 10px;">Salir</a>
        </div>
    </header>

    <!-- Dashboard content will be included here -->
</body>
</html>
