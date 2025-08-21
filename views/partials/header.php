<header class="header">
    <div class="header-left">
        <img src="assets/images/netflix-logo.png" alt="Netflix" class="logo">
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Inicio</a>
            <a href="series.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'series.php' ? 'active' : ''; ?>">Series</a>
            <a href="movies.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'movies.php' ? 'active' : ''; ?>">Películas</a>
            <a href="my-list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-list.php' ? 'active' : ''; ?>">Mi lista</a>
        </nav>
    </div>
    
    <div class="header-right">
        <!-- Búsqueda -->
        <div class="search-container">
            <button class="search-toggle" onclick="toggleSearch()">
                <i class="fas fa-search"></i>
            </button>
            <div class="search-box" id="searchBox">
                <input type="text" id="searchInput" placeholder="Títulos, personas, géneros">
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
        </div>
        
        <!-- Notificaciones -->
        <div class="notifications-container">
            <button class="notifications-toggle" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge">0</span>
            </button>
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h3>Notificaciones</h3>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <div class="notification-item">
                        <div class="notification-loading">Cargando notificaciones...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Perfil -->
        <div class="profile-container">
            <button class="profile-toggle" onclick="toggleProfileMenu()">
                <img src="assets/images/avatars/avatar1.png" alt="Perfil" class="profile-avatar">
                <i class="fas fa-caret-down"></i>
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-info">
                    <img src="assets/images/avatars/avatar1.png" alt="Perfil" class="profile-avatar-large">
                    <span class="profile-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                </div>
                <div class="profile-menu">
                    <a href="manage-profiles.php" class="profile-menu-item">
                        <i class="fas fa-user-edit"></i> Administrar perfiles
                    </a>
                    <a href="edit-profile.php" class="profile-menu-item">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="admin-dashboard.php" class="profile-menu-item">
                        <i class="fas fa-shield-alt"></i> Panel de administración
                    </a>
                    <?php endif; ?>
                    <div class="profile-menu-divider"></div>
                    <a href="logout.php" class="profile-menu-item">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
let searchTimeout;
let isSearchOpen = false;
let isNotificationsOpen = false;
let isProfileOpen = false;

// Búsqueda
function toggleSearch() {
    const searchBox = document.getElementById('searchBox');
    const searchInput = document.getElementById('searchInput');
    
    isSearchOpen = !isSearchOpen;
    
    if (isSearchOpen) {
        searchBox.classList.add('active');
        searchInput.focus();
    } else {
        searchBox.classList.remove('active');
        searchInput.value = '';
        document.getElementById('searchSuggestions').innerHTML = '';
    }
}

document.getElementById('searchInput').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        document.getElementById('searchSuggestions').innerHTML = '';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchSuggestions(data.suggestions);
                }
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
            });
    }, 300);
});

function displaySearchSuggestions(suggestions) {
    const container = document.getElementById('searchSuggestions');
    
    if (suggestions.length === 0) {
        container.innerHTML = '<div class="no-suggestions">No se encontraron resultados</div>';
        return;
    }
    
    const html = suggestions.map(item => `
        <div class="suggestion-item" onclick="goToContent(${item.id})">
            <div class="suggestion-info">
                <div class="suggestion-title">${item.title}</div>
                <div class="suggestion-meta">${item.type === 'movie' ? 'Película' : 'Serie'} • ${item.genre} • ${item.release_year}</div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

function goToContent(contentId) {
    window.location.href = `content-details.php?id=${contentId}`;
}

// Notificaciones
function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    
    isNotificationsOpen = !isNotificationsOpen;
    
    if (isNotificationsOpen) {
        dropdown.classList.add('active');
        loadNotifications();
    } else {
        dropdown.classList.remove('active');
    }
}

function loadNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Error cargando notificaciones:', error);
            document.getElementById('notificationsList').innerHTML = 
                '<div class="notification-error">Error al cargar notificaciones</div>';
        });
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="no-notifications">No tienes notificaciones</div>';
        return;
    }
    
    const html = notifications.map(notification => `
        <div class="notification-item ${notification.read ? 'read' : 'unread'}" 
             ${notification.content_id ? `onclick="goToContent(${notification.content_id})"` : ''}>
            <div class="notification-icon">
                <i class="fas ${getNotificationIcon(notification.type)}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${formatTime(notification.time)}</div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

function getNotificationIcon(type) {
    switch (type) {
        case 'new_content': return 'fa-plus-circle';
        case 'watchlist_update': return 'fa-heart';
        case 'recommendation': return 'fa-star';
        default: return 'fa-bell';
    }
}

function formatTime(timeString) {
    const date = new Date(timeString);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) return `hace ${days} día${days > 1 ? 's' : ''}`;
    if (hours > 0) return `hace ${hours} hora${hours > 1 ? 's' : ''}`;
    if (minutes > 0) return `hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
    return 'hace un momento';
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    badge.textContent = count;
    badge.style.display = count > 0 ? 'block' : 'none';
}

// Menú de perfil
function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    
    isProfileOpen = !isProfileOpen;
    
    if (isProfileOpen) {
        dropdown.classList.add('active');
    } else {
        dropdown.classList.remove('active');
    }
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        if (isSearchOpen) toggleSearch();
    }
    
    if (!e.target.closest('.notifications-container')) {
        if (isNotificationsOpen) toggleNotifications();
    }
    
    if (!e.target.closest('.profile-container')) {
        if (isProfileOpen) toggleProfileMenu();
    }
});

// Cargar notificaciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Error cargando badge de notificaciones:', error);
        });
});
</script>
