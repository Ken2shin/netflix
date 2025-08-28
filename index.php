<?php
session_start();

// Enable output buffering for better performance
ob_start();

// Set performance headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'services/OMDBService.php';

// Si el usuario ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit();
}

// Handle OMDB search if query is provided
$searchResults = [];
$searchQuery = $_GET['search'] ?? '';

if (!empty($searchQuery)) {
    try {
        $omdbService = new OMDBService();
        $results = $omdbService->searchMovies($searchQuery);
        if ($results && isset($results['Search'])) {
            $searchResults = $results['Search'];
        }
    } catch (Exception $e) {
        error_log("OMDB Search Error: " . $e->getMessage());
    }
}

// Mostrar página de landing
include 'views/landing.php';
exit;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix - Streaming Platform</title>
    
    <!-- Added performance and SEO meta tags -->
    <meta name="description" content="Netflix - Unlimited movies, TV shows, and more. Watch anywhere. Cancel anytime.">
    <meta name="keywords" content="netflix, streaming, movies, tv shows, entertainment">
    <meta name="author" content="Netflix Clone">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/images/netflix-logo.png" as="image">
    <link rel="preload" href="assets/fonts/netflix-sans.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <style>
        /* Optimized CSS with better performance and modern features */
        :root {
            --netflix-red: #e50914;
            --netflix-red-hover: #f40612;
            --netflix-black: #141414;
            --netflix-gray: #b3b3b3;
            --netflix-dark-gray: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Netflix Sans', 'Helvetica Neue', Arial, sans-serif;
            background: var(--netflix-black);
            color: white;
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);
            z-index: 1000;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            transition: background-color 0.3s ease;
        }

        .header.scrolled {
            background: rgba(20, 20, 20, 0.95);
        }

        .logo {
            height: 25px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
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
            font-weight: 400;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--netflix-red);
            transition: width 0.3s ease;
        }

        .nav-menu a:hover::after {
            width: 100%;
        }

        .nav-menu a:hover {
            color: var(--netflix-gray);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: var(--netflix-dark-gray);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .profile-avatar:hover {
            transform: scale(1.1);
        }

        .main-content {
            padding-top: 80px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, rgba(20,20,20,0.9), rgba(0,0,0,0.8));
        }

        .welcome-message {
            font-size: clamp(32px, 8vw, 48px);
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease-out;
        }

        .subtitle {
            font-size: clamp(18px, 4vw, 24px);
            color: var(--netflix-gray);
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .cta-button {
            background: var(--netflix-red);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease-out 0.4s both;
            position: relative;
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button:hover {
            background: var(--netflix-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(229, 9, 20, 0.3);
        }

        /* Added loading spinner and connection status */
        .connection-status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .connection-status.connected {
            background: #28a745;
            color: white;
        }

        .connection-status.disconnected {
            background: #dc3545;
            color: white;
        }

        .connection-status.connecting {
            background: #ffc107;
            color: #212529;
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(229, 9, 20, 0.3);
            border-top: 4px solid var(--netflix-red);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-container form {
            display: flex;
            gap: 10px;
        }

        .search-container input {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(10px);
        }

        .search-container button {
            padding: 8px 15px;
            background: var(--netflix-red);
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }

        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(20,20,20,0.95);
            border-radius: 4px;
            backdrop-filter: blur(10px);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        }

        .search-dropdown div {
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s ease;
        }

        .search-dropdown img {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .search-dropdown div:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .movie-card {
            background: rgba(40,40,40,0.8);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .movie-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .movie-card div {
            padding: 15px;
        }

        .movie-card h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: white;
        }

        .movie-card p {
            font-size: 14px;
            color: var(--netflix-gray);
        }

        .movie-card p:last-child {
            font-size: 12px;
            color: var(--netflix-gray);
            text-transform: capitalize;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 740px) {
            .header {
                padding: 20px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .user-menu {
                gap: 10px;
            }
        }

        /* Added performance optimizations for animations */
        .header, .nav-menu a, .profile-avatar, .cta-button {
            will-change: transform;
        }
    </style>
</head>
<body>
    <!-- Added connection status indicator -->
    <div id="connectionStatus" class="connection-status connecting">
        <span id="statusText">Conectando...</span>
    </div>

    <!-- Added loading spinner -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <header class="header" id="mainHeader">
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
            <a href="content-details.php?id=1" class="cta-button">
                <i class="fas fa-play"></i> Ver ahora
            </a>
            <img src="assets/images/avatars/avatar1.png" alt="Perfil" class="profile-avatar">
            <a href="profiles.php" style="color: white; text-decoration: none;">Cambiar perfil</a>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 10px;">Salir</a>
        </div>

        <!-- Added search functionality in header -->
        <div class="search-container">
            <form method="GET">
                <input type="text" name="search" placeholder="Buscar películas..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- Dashboard content will be included here -->

    <!-- Added search results section -->
    <?php if (!empty($searchResults)): ?>
    <section class="search-results">
        <h2>
            Resultados para "<?php echo htmlspecialchars($searchQuery); ?>"
        </h2>
        <div class="results-grid">
            <?php foreach ($searchResults as $movie): ?>
            <div class="movie-card"
                 onclick="window.location.href='content-details.php?imdb_id=<?php echo urlencode($movie['imdbID']); ?>'">
                <img src="<?php echo $movie['Poster'] !== 'N/A' ? htmlspecialchars($movie['Poster']) : 'assets/images/no-poster.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($movie['Title']); ?>">
                <div>
                    <h3><?php echo htmlspecialchars($movie['Title']); ?></h3>
                    <p><?php echo htmlspecialchars($movie['Year']); ?></p>
                    <p><?php echo htmlspecialchars($movie['Type']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Added WebSocket integration and performance optimizations -->
    <script>
        // WebSocket connection for real-time updates
        class NetflixWebSocket {
            constructor() {
                this.ws = null;
                this.reconnectAttempts = 0;
                this.maxReconnectAttempts = 5;
                this.reconnectDelay = 1000;
                this.heartbeatInterval = null;
                this.init();
            }

            init() {
                this.connect();
                this.setupEventListeners();
            }

            connect() {
                try {
                    // Use secure WebSocket if HTTPS, otherwise regular WebSocket
                    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                    const wsUrl = `${protocol}//${window.location.host}/ws`;
                    
                    this.ws = new WebSocket(wsUrl);
                    this.setupWebSocketEvents();
                    this.updateConnectionStatus('connecting', 'Conectando...');
                } catch (error) {
                    console.error('WebSocket connection error:', error);
                    this.handleConnectionError();
                }
            }

            setupWebSocketEvents() {
                this.ws.onopen = () => {
                    console.log('[v0] WebSocket connected successfully');
                    this.reconnectAttempts = 0;
                    this.updateConnectionStatus('connected', 'Conectado');
                    this.startHeartbeat();
                    this.hideLoadingSpinner();
                };

                this.ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.handleMessage(data);
                    } catch (error) {
                        console.error('[v0] Error parsing WebSocket message:', error);
                    }
                };

                this.ws.onclose = (event) => {
                    console.log('[v0] WebSocket connection closed:', event.code, event.reason);
                    this.stopHeartbeat();
                    this.updateConnectionStatus('disconnected', 'Desconectado');
                    this.attemptReconnect();
                };

                this.ws.onerror = (error) => {
                    console.error('[v0] WebSocket error:', error);
                    this.handleConnectionError();
                };
            }

            handleMessage(data) {
                switch (data.type) {
                    case 'content_update':
                        this.handleContentUpdate(data.payload);
                        break;
                    case 'user_notification':
                        this.showNotification(data.payload);
                        break;
                    case 'system_status':
                        this.handleSystemStatus(data.payload);
                        break;
                    default:
                        console.log('[v0] Unknown message type:', data.type);
                }
            }

            handleContentUpdate(payload) {
                // Handle real-time content updates
                if (payload.action === 'new_content') {
                    this.showNotification({
                        title: 'Nuevo contenido disponible',
                        message: payload.title,
                        type: 'info'
                    });
                }
            }

            showNotification(notification) {
                // Create and show notification
                const notificationEl = document.createElement('div');
                notificationEl.className = 'notification';
                notificationEl.innerHTML = `
                    <div class="notification-content">
                        <h4>${notification.title}</h4>
                        <p>${notification.message}</p>
                    </div>
                `;
                document.body.appendChild(notificationEl);
                
                setTimeout(() => {
                    notificationEl.remove();
                }, 5000);
            }

            startHeartbeat() {
                this.heartbeatInterval = setInterval(() => {
                    if (this.ws.readyState === WebSocket.OPEN) {
                        this.ws.send(JSON.stringify({ type: 'ping' }));
                    }
                }, 30000);
            }

            stopHeartbeat() {
                if (this.heartbeatInterval) {
                    clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = null;
                }
            }

            attemptReconnect() {
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    this.updateConnectionStatus('connecting', `Reconectando... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                    
                    setTimeout(() => {
                        this.connect();
                    }, this.reconnectDelay * this.reconnectAttempts);
                } else {
                    this.updateConnectionStatus('disconnected', 'Sin conexión');
                }
            }

            updateConnectionStatus(status, text) {
                const statusEl = document.getElementById('connectionStatus');
                const textEl = document.getElementById('statusText');
                
                if (statusEl && textEl) {
                    statusEl.className = `connection-status ${status}`;
                    textEl.textContent = text;
                }
            }

            showLoadingSpinner() {
                const spinner = document.getElementById('loadingSpinner');
                if (spinner) spinner.style.display = 'block';
            }

            hideLoadingSpinner() {
                const spinner = document.getElementById('loadingSpinner');
                if (spinner) spinner.style.display = 'none';
            }

            handleConnectionError() {
                this.updateConnectionStatus('disconnected', 'Error de conexión');
                this.hideLoadingSpinner();
            }

            setupEventListeners() {
                // Handle page visibility changes
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stopHeartbeat();
                    } else {
                        if (this.ws.readyState === WebSocket.OPEN) {
                            this.startHeartbeat();
                        } else {
                            this.connect();
                        }
                    }
                });

                // Handle scroll events for header
                let scrollTimeout;
                window.addEventListener('scroll', () => {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        const header = document.getElementById('mainHeader');
                        if (header) {
                            if (window.scrollY > 50) {
                                header.classList.add('scrolled');
                            } else {
                                header.classList.remove('scrolled');
                            }
                        }
                    }, 10);
                });
            }
        }

        // Performance optimizations
        class PerformanceOptimizer {
            constructor() {
                this.init();
            }

            init() {
                this.preloadCriticalResources();
                this.setupLazyLoading();
                this.optimizeImages();
                this.setupServiceWorker();
            }

            preloadCriticalResources() {
                const criticalResources = [
                    'assets/css/netflix-main.css',
                    'assets/js/netflix-core.js'
                ];

                criticalResources.forEach(resource => {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.href = resource;
                    link.as = resource.endsWith('.css') ? 'style' : 'script';
                    document.head.appendChild(link);
                });
            }

            setupLazyLoading() {
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        });
                    });

                    document.querySelectorAll('img[data-src]').forEach(img => {
                        imageObserver.observe(img);
                    });
                }
            }

            optimizeImages() {
                // Convert images to WebP if supported
                const supportsWebP = () => {
                    const canvas = document.createElement('canvas');
                    return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
                };

                if (supportsWebP()) {
                    document.querySelectorAll('img').forEach(img => {
                        if (img.src && !img.src.includes('.webp')) {
                            const webpSrc = img.src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
                            img.src = webpSrc;
                        }
                    });
                }
            }

            setupServiceWorker() {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => {
                            console.log('[v0] Service Worker registered successfully');
                        })
                        .catch(error => {
                            console.log('[v0] Service Worker registration failed');
                        });
                }
            }
        }

        class NetflixSearch {
            constructor() {
                this.searchInput = document.querySelector('input[name="search"]');
                this.searchForm = document.querySelector('form');
                this.setupAutoComplete();
            }

            setupAutoComplete() {
                if (!this.searchInput) return;

                let searchTimeout;
                this.searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    const query = e.target.value.trim();
                    
                    if (query.length >= 3) {
                        searchTimeout = setTimeout(() => {
                            this.performAutoComplete(query);
                        }, 300);
                    }
                });
            }

            async performAutoComplete(query) {
                try {
                    const response = await fetch(`api/search-omdb.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    if (data.success && data.results.length > 0) {
                        this.showAutoCompleteResults(data.results.slice(0, 5));
                    }
                } catch (error) {
                    console.error('[v0] Auto-complete search failed:', error);
                }
            }

            showAutoCompleteResults(results) {
                // Remove existing dropdown
                const existingDropdown = document.querySelector('.search-dropdown');
                if (existingDropdown) {
                    existingDropdown.remove();
                }

                // Create new dropdown
                const dropdown = document.createElement('div');
                dropdown.className = 'search-dropdown';

                results.forEach(movie => {
                    const item = document.createElement('div');
                    item.style.cssText = `
                        padding: 10px 15px;
                        border-bottom: 1px solid rgba(255,255,255,0.1);
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        transition: background-color 0.2s ease;
                    `;
                    
                    item.innerHTML = `
                        <img src="${movie.Poster !== 'N/A' ? movie.Poster : 'assets/images/no-poster.jpg'}" 
                             style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">
                        <div>
                            <div style="color: white; font-weight: 600;">${movie.Title}</div>
                            <div style="color: var(--netflix-gray); font-size: 12px;">${movie.Year} • ${movie.Type}</div>
                        </div>
                    `;
                    
                    item.addEventListener('mouseenter', () => {
                        item.style.backgroundColor = 'rgba(255,255,255,0.1)';
                    });
                    
                    item.addEventListener('mouseleave', () => {
                        item.style.backgroundColor = 'transparent';
                    });
                    
                    item.addEventListener('click', () => {
                        window.location.href = `content-details.php?imdb_id=${movie.imdbID}`;
                    });
                    
                    dropdown.appendChild(item);
                });

                // Position dropdown relative to search input
                const searchContainer = this.searchInput.closest('.search-container');
                if (searchContainer) {
                    searchContainer.style.position = 'relative';
                    searchContainer.appendChild(dropdown);
                }

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!searchContainer.contains(e.target)) {
                        dropdown.remove();
                    }
                }, { once: true });
            }
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[v0] Initializing Netflix application');
            
            // Initialize WebSocket connection
            window.netflixWS = new NetflixWebSocket();
            
            // Initialize performance optimizations
            window.perfOptimizer = new PerformanceOptimizer();
            
            // Initialize search
            window.netflixSearch = new NetflixSearch();
            
            console.log('[v0] Netflix application initialized successfully');
        });

        // Handle page unload
        window.addEventListener('beforeunload', () => {
            if (window.netflixWS && window.netflixWS.ws) {
                window.netflixWS.ws.close();
            }
        });
    </script>
</body>
</html>
