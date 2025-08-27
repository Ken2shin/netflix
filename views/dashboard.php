<?php
require_once 'config/database.php';

// Datos de ejemplo para el dashboard
$featuredContent = [
    'title' => 'Stranger Things',
    'description' => 'Cuando un niño desaparece, su madre, un jefe de policía y sus amigos deben enfrentar fuerzas terroríficas para recuperarlo.',
    'image' => '/placeholder.svg?height=600&width=1200&text=Stranger+Things'
];

$contentRows = [
    [
        'title' => 'Tendencias ahora',
        'items' => [
            ['title' => 'The Witcher', 'image' => '/placeholder.svg?height=300&width=200&text=The+Witcher'],
            ['title' => 'Peaky Blinders', 'image' => '/placeholder.svg?height=300&width=200&text=Peaky+Blinders'],
            ['title' => 'Walking Dead', 'image' => '/placeholder.svg?height=300&width=200&text=Walking+Dead'],
            ['title' => 'Red Notice', 'image' => '/placeholder.svg?height=300&width=200&text=Red+Notice'],
            ['title' => 'Manifest', 'image' => '/placeholder.svg?height=300&width=200&text=Manifest'],
            ['title' => 'Dahmer', 'image' => '/placeholder.svg?height=300&width=200&text=Dahmer'],
            ['title' => 'Ozark', 'image' => '/placeholder.svg?height=300&width=200&text=Ozark'],
            ['title' => 'Squid Game', 'image' => '/placeholder.svg?height=300&width=200&text=Squid+Game']
        ]
    ],
    [
        'title' => 'Populares en Netflix',
        'items' => [
            ['title' => 'Lucifer', 'image' => '/placeholder.svg?height=300&width=200&text=Lucifer'],
            ['title' => 'Flash', 'image' => '/placeholder.svg?height=300&width=200&text=Flash'],
            ['title' => 'MIB International', 'image' => '/placeholder.svg?height=300&width=200&text=MIB'],
            ['title' => 'La Crue', 'image' => '/placeholder.svg?height=300&width=200&text=La+Crue'],
            ['title' => 'Miraculous', 'image' => '/placeholder.svg?height=300&width=200&text=Miraculous'],
            ['title' => 'Cobra Kai', 'image' => '/placeholder.svg?height=300&width=200&text=Cobra+Kai'],
            ['title' => 'Money Heist', 'image' => '/placeholder.svg?height=300&width=200&text=Money+Heist'],
            ['title' => 'Dark', 'image' => '/placeholder.svg?height=300&width=200&text=Dark']
        ]
    ],
    [
        'title' => 'Mi lista',
        'items' => [
            ['title' => 'Downton Abbey', 'image' => '/placeholder.svg?height=300&width=200&text=Downton+Abbey'],
            ['title' => 'Purple Hearts', 'image' => '/placeholder.svg?height=300&width=200&text=Purple+Hearts'],
            ['title' => 'The Midnight Club', 'image' => '/placeholder.svg?height=300&width=200&text=Midnight+Club'],
            ['title' => 'Tous en Scene', 'image' => '/placeholder.svg?height=300&width=200&text=Tous+en+Scene'],
            ['title' => 'Athena', 'image' => '/placeholder.svg?height=300&width=200&text=Athena'],
            ['title' => 'American Girl', 'image' => '/placeholder.svg?height=300&width=200&text=American+Girl'],
            ['title' => 'Wednesday', 'image' => '/placeholder.svg?height=300&width=200&text=Wednesday'],
            ['title' => 'Glass Onion', 'image' => '/placeholder.svg?height=300&width=200&text=Glass+Onion']
        ]
    ],
    [
        'title' => 'Acción y Aventura',
        'items' => [
            ['title' => 'Extraction', 'image' => '/placeholder.svg?height=300&width=200&text=Extraction'],
            ['title' => '6 Underground', 'image' => '/placeholder.svg?height=300&width=200&text=6+Underground'],
            ['title' => 'The Old Guard', 'image' => '/placeholder.svg?height=300&width=200&text=The+Old+Guard'],
            ['title' => 'Triple Frontier', 'image' => '/placeholder.svg?height=300&width=200&text=Triple+Frontier'],
            ['title' => 'Bird Box', 'image' => '/placeholder.svg?height=300&width=200&text=Bird+Box'],
            ['title' => 'The Platform', 'image' => '/placeholder.svg?height=300&width=200&text=The+Platform'],
            ['title' => 'Army of the Dead', 'image' => '/placeholder.svg?height=300&width=200&text=Army+of+the+Dead'],
            ['title' => 'Sweet Tooth', 'image' => '/placeholder.svg?height=300&width=200&text=Sweet+Tooth']
        ]
    ]
];
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
            overflow-x: hidden;
        }

        /* Header */
        .netflix-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(180deg, rgba(0,0,0,0.7) 10%, transparent);
            z-index: 1000;
            padding: 10px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.4s;
        }

        .netflix-header.scrolled {
            background-color: #141414;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .netflix-logo {
            height: 25px;
        }

        .main-nav {
            display: flex;
            gap: 20px;
        }

        .main-nav a {
            color: #e5e5e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: color 0.4s;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: #b3b3b3;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-icon, .notifications-icon {
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .search-icon:hover, .notifications-icon:hover {
            color: #b3b3b3;
        }

        .profile-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
        }

        /* Hero Section */
        .hero-section {
            height: 56.25vw;
            min-height: 600px;
            max-height: 800px;
            background: linear-gradient(77deg, rgba(0,0,0,.6), transparent 85%), url('<?php echo $featuredContent['image']; ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            position: relative;
        }

        .hero-content {
            padding: 0 4%;
            max-width: 50%;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }

        .hero-description {
            font-size: 1.4rem;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.45);
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-play {
            background-color: white;
            color: black;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-play:hover {
            background-color: rgba(255,255,255,0.75);
        }

        .btn-info {
            background-color: rgba(109, 109, 110, 0.7);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-info:hover {
            background-color: rgba(109, 109, 110, 0.4);
        }

        /* Content Sections */
        .content-sections {
            margin-top: -150px;
            position: relative;
            z-index: 1;
            padding: 0 4%;
        }

        .content-row {
            margin-bottom: 3rem;
        }

        .content-row h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #e5e5e5;
        }

        .content-slider {
            display: flex;
            gap: 0.25rem;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .content-slider::-webkit-scrollbar {
            display: none;
        }

        .content-item {
            flex: 0 0 auto;
            width: 200px;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .content-item:hover {
            transform: scale(1.05);
            z-index: 2;
        }

        .content-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 4px;
        }

        .content-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 0 0 4px 4px;
        }

        .content-item:hover .content-overlay {
            opacity: 1;
        }

        .content-overlay h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .content-actions {
            display: flex;
            gap: 0.5rem;
        }

        .content-actions button {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #808080;
            background: transparent;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .content-actions button:hover {
            border-color: white;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .header-left {
                gap: 20px;
            }
            
            .main-nav {
                display: none;
            }
            
            .hero-content {
                max-width: 80%;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .content-item {
                width: 150px;
            }
            
            .content-item img {
                height: 225px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="netflix-header" id="netflixHeader">
        <div class="header-left">
            <img src="assets/images/netflix-logo.png" alt="Netflix" class="netflix-logo">
            <nav class="main-nav">
                <a href="index.php" class="active">Inicio</a>
                <a href="series.php">Series</a>
                <a href="movies.php">Películas</a>
                <a href="my-list.php">Mi lista</a>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="search-icon">🔍</div>
            <div class="notifications-icon">🔔</div>
            
            <div class="profile-menu">
                <img src="assets/images/avatars/<?php echo $_SESSION['profile_avatar'] ?? 'avatar1.png'; ?>" 
                     alt="<?php echo $_SESSION['profile_name'] ?? 'Perfil'; ?>" 
                     class="profile-avatar">
                <span>▼</span>
            </div>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title"><?php echo $featuredContent['title']; ?></h1>
            <p class="hero-description"><?php echo $featuredContent['description']; ?></p>
            
            <div class="hero-buttons">
                <button class="btn-play">
                    ▶ Reproducir
                </button>
                <button class="btn-info">
                    ℹ Más información
                </button>
            </div>
        </div>
    </section>
    
    <!-- Content Sections -->
    <div class="content-sections">
        <?php foreach ($contentRows as $row): ?>
        <section class="content-row">
            <h2><?php echo $row['title']; ?></h2>
            <div class="content-slider">
                <?php foreach ($row['items'] as $item): ?>
                    <div class="content-item">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>">
                        <div class="content-overlay">
                            <h3><?php echo $item['title']; ?></h3>
                            <div class="content-actions">
                                <button title="Reproducir">▶</button>
                                <button title="Agregar a Mi Lista">+</button>
                                <button title="Me gusta">👍</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('netflixHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Slider horizontal con mouse
        document.querySelectorAll('.content-slider').forEach(slider => {
            let isDown = false;
            let startX;
            let scrollLeft;
            
            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
                slider.style.cursor = 'grabbing';
            });
            
            slider.addEventListener('mouseleave', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            
            slider.addEventListener('mouseup', () => {
                isDown = false;
                slider.style.cursor = 'grab';
            });
            
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 2;
                slider.scrollLeft = scrollLeft - walk;
            });
        });
    </script>
</body>
</html>
