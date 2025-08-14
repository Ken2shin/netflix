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
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/netflix-background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 60px;
            z-index: 10;
        }

        .logo {
            height: 45px;
        }

        .sign-in-btn {
            background: #e50914;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 400;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sign-in-btn:hover {
            background: #f40612;
        }

        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 150px 20px;
            max-width: 950px;
            margin: 0 auto;
            z-index: 5;
        }

        .hero-content {
            max-width: 950px;
        }

        .hero-title {
            font-size: 3.125rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }

        .hero-subtitle {
            font-size: 1.625rem;
            font-weight: 400;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }

        .hero-description {
            font-size: 1.125rem;
            font-weight: 400;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }

        .cta-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .cta-btn {
            background: #e50914;
            color: white;
            border: none;
            padding: 16px 32px;
            font-size: 1.5rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .cta-btn:hover {
            background: #f40612;
        }

        .features {
            background-color: #000;
            padding: 70px 0;
        }

        .feature {
            display: flex;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
            padding: 70px 45px;
            border-bottom: 8px solid #222;
        }

        .feature:nth-child(even) {
            flex-direction: row-reverse;
        }

        .feature-text {
            flex: 1;
            padding: 0 3rem;
        }

        .feature-title {
            font-size: 3.125rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            font-size: 1.625rem;
            font-weight: 400;
            color: #999;
        }

        .feature-image {
            flex: 1;
            text-align: center;
        }

        .feature-image img {
            max-width: 100%;
            height: auto;
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.125rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .email-signup {
                flex-direction: column;
                gap: 16px;
            }
            
            .email-input {
                border-radius: 4px;
            }
            
            .get-started-btn {
                border-radius: 4px;
            }
            
            .feature {
                flex-direction: column !important;
                text-align: center;
                padding: 40px 20px;
            }
            
            .feature-text {
                padding: 0 0 3rem 0;
            }
            
            .feature-title {
                font-size: 2rem;
            }
            
            .feature-description {
                font-size: 1.125rem;
            }
        }

        @media (max-width: 740px) {
            .header {
                padding: 20px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero h2 {
                font-size: 1.25rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .cta-btn {
                font-size: 1.125rem;
                padding: 12px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/images/netflix-logo.png" alt="Netflix" class="logo">
        <a href="login.php" class="sign-in-btn">Iniciar sesión</a>
    </div>
    
    <div class="hero">
        <h1 class="hero-title">Películas y series ilimitadas y mucho más</h1>
        <h2 class="hero-subtitle">Disfruta donde quieras. Cancela cuando quieras.</h2>
        <p class="hero-description">¿Quieres ver Netflix ya? Ingresa tu email para crear una cuenta o reiniciar tu membresía de Netflix.</p>
        
        <div class="cta-container">
            <a href="register.php" class="cta-btn">Comenzar</a>
        </div>
    </div>

    <div class="features">
        <div class="feature">
            <div class="feature-text">
                <h2 class="feature-title">Disfruta en tu TV</h2>
                <p class="feature-description">Ve en smart TV, PlayStation, Xbox, Chromecast, Apple TV, reproductores de Blu-ray y más.</p>
            </div>
            <div class="feature-image">
                <img src="/placeholder.svg?height=400&width=550&text=TV+Feature" alt="Disfruta en tu TV">
            </div>
        </div>

        <div class="feature">
            <div class="feature-text">
                <h2 class="feature-title">Descarga tus series para verlas offline</h2>
                <p class="feature-description">Guarda fácilmente tus favoritos y siempre tendrás algo para ver.</p>
            </div>
            <div class="feature-image">
                <img src="/placeholder.svg?height=400&width=550&text=Download+Feature" alt="Descarga series">
            </div>
        </div>

        <div class="feature">
            <div class="feature-text">
                <h2 class="feature-title">Ve donde quieras</h2>
                <p class="feature-description">Películas y series ilimitadas en tu teléfono, tablet, computadora y TV.</p>
            </div>
            <div class="feature-image">
                <img src="/placeholder.svg?height=400&width=550&text=Watch+Anywhere" alt="Ve donde quieras">
            </div>
        </div>
    </div>
</body>
</html>
