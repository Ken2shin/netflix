<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Películas y series ilimitadas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #000;
            color: white;
        }

        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .hero-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 2rem 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .netflix-logo {
            color: #e50914;
            font-size: 2rem;
            font-weight: 700;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-login {
            background: #e50914;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #f40612;
        }

        .btn-register {
            background: transparent;
            color: white;
            padding: 0.5rem 1rem;
            border: 1px solid white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-register:hover {
            background: white;
            color: black;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 2rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .hero-description {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .cta-button {
            background: #e50914;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .cta-button:hover {
            background: #f40612;
        }

        .features-section {
            padding: 4rem 4%;
            background: #000;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-item {
            text-align: center;
            padding: 2rem;
            position: relative;
        }

        .feature-image {
            width: 100%;
            max-width: 400px;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(229, 9, 20, 0.3);
            transition: transform 0.3s ease;
        }

        .feature-image:hover {
            transform: scale(1.05);
        }

        .feature-icon {
            font-size: 3rem;
            color: #e50914;
            margin-bottom: 1rem;
            display: none; /* Hide emoji icons when we have real images */
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: #b3b3b3;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .auth-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-header">
            <div class="netflix-logo">NETFLIX</div>
            <div class="auth-buttons">
                <a href="login.php" class="btn-login">Iniciar Sesión</a>
                <a href="register.php" class="btn-register">Registrarse</a>
            </div>
        </div>

        <div class="hero-content">
            <h1 class="hero-title">Películas y series ilimitadas y mucho más</h1>
            <h2 class="hero-subtitle">Disfruta donde quieras. Cancela cuando quieras.</h2>
            <p class="hero-description">¿Quieres ver Netflix ya? Ingresa tu email para crear una cuenta o reiniciar tu membresía de Netflix.</p>
            <a href="register.php" class="cta-button">Comenzar</a>
        </div>
    </div>

    <div class="features-section">
        <div class="features-grid">
            <div class="feature-item">
                <img src="assets/images/netflix-tv-interface.png" alt="Disfruta en tu TV" class="feature-image">
                <div class="feature-icon">📺</div>
                <h3 class="feature-title">Disfruta en tu TV</h3>
                <p class="feature-description">Ve en smart TV, PlayStation, Xbox, Chromecast, Apple TV, reproductores de Blu-ray y más.</p>
            </div>

            <div class="feature-item">
                <img src="assets/images/netflix-tablet-popcorn.png" alt="Descarga tus series para verlas offline" class="feature-image">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Descarga tus series para verlas offline</h3>
                <p class="feature-description">Guarda fácilmente tus favoritos y siempre tendrás algo para ver.</p>
            </div>

            <div class="feature-item">
                <img src="assets/images/streaming-apps.png" alt="Ve donde quieras" class="feature-image">
                <div class="feature-icon">👨‍👩‍👧‍👦</div>
                <h3 class="feature-title">Ve donde quieras</h3>
                <p class="feature-description">Transmite películas y programas de TV ilimitados en tu teléfono, tablet, laptop y TV.</p>
            </div>
        </div>
    </div>
</body>
</html>
