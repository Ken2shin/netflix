<?php
try {
    $current_user = getCurrentUser();
    $current_profile = getCurrentProfile();
} catch (Exception $e) {
    error_log("Error in header: " . $e->getMessage());
    $current_user = null;
    $current_profile = ['name' => 'Usuario', 'avatar' => 'avatar1.png'];
}
?>

<header class="netflix-header">
    <div class="header-left">
        <div class="netflix-logo">
            <img src="assets/images/netflix-logo.png" alt="Netflix" onclick="location.href='dashboard.php'">
        </div>
        <nav class="main-nav">
            <a href="dashboard.php" class="nav-link">Inicio</a>
            <a href="movies.php" class="nav-link">Películas</a>
            <a href="series.php" class="nav-link">Series</a>
            <a href="my-list.php" class="nav-link">Mi lista</a>
        </nav>
    </div>
    
    <div class="header-right">
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Buscar..." class="search-input">
            <button class="search-btn">🔍</button>
        </div>
        
        <!-- Added notification bell with badge -->
        <div class="notification-menu">
            <div class="notification-bell" onclick="toggleNotificationMenu()">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </div>
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h4>Notificaciones</h4>
                    <button class="mark-all-read" onclick="markAllAsRead()">Marcar todas como leídas</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="loading-notifications">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-menu">
            <div class="profile-avatar" onclick="toggleProfileMenu()">
                <img src="assets/images/avatars/<?php echo htmlspecialchars($current_profile['avatar'] ?? 'avatar1.png'); ?>" 
                     alt="<?php echo htmlspecialchars($current_profile['name'] ?? 'Usuario'); ?>">
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="dropdown-item">
                    <img src="assets/images/avatars/<?php echo htmlspecialchars($current_profile['avatar'] ?? 'avatar1.png'); ?>" 
                         alt="Profile">
                    <span><?php echo htmlspecialchars($current_profile['name'] ?? 'Usuario'); ?></span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profiles.php" class="dropdown-item">Cambiar perfil</a>
                <a href="manage-profiles.php" class="dropdown-item">Administrar perfiles</a>
                <?php if (isAdmin()): ?>
                    <div class="dropdown-divider"></div>
                    <a href="admin-dashboard.php" class="dropdown-item">Panel Admin</a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">Cerrar sesión</a>
            </div>
        </div>
    </div>
</header>

<style>
.netflix-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: linear-gradient(180deg, rgba(0,0,0,0.7) 10%, transparent);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 60px;
    z-index: 1000;
    transition: background-color 0.4s;
}

.netflix-header.scrolled {
    background: #141414;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 40px;
}

.netflix-logo img {
    height: 30px;
    cursor: pointer;
}

.main-nav {
    display: flex;
    gap: 20px;
}

.nav-link {
    color: #e5e5e5;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.nav-link:hover {
    color: #b3b3b3;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.search-container {
    display: flex;
    align-items: center;
    background: rgba(0,0,0,0.75);
    border: 1px solid #333;
    border-radius: 4px;
    padding: 5px 10px;
}

.search-input {
    background: transparent;
    border: none;
    color: white;
    padding: 5px;
    width: 200px;
    outline: none;
}

.search-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
}

/* Added notification bell styles */
.notification-menu {
    position: relative;
}

.notification-bell {
    position: relative;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #e5e5e5;
    transition: color 0.3s;
}

.notification-bell:hover {
    color: white;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e50914;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: rgba(0,0,0,0.95);
    border: 1px solid #333;
    border-radius: 8px;
    width: 350px;
    max-height: 400px;
    display: none;
    z-index: 1001;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 15px 20px;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h4 {
    color: white;
    margin: 0;
    font-size: 16px;
}

.mark-all-read {
    background: none;
    border: none;
    color: #e50914;
    cursor: pointer;
    font-size: 12px;
    text-decoration: underline;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #333;
    cursor: pointer;
    transition: background-color 0.3s;
    position: relative;
}

.notification-item:hover {
    background: rgba(255,255,255,0.05);
}

.notification-item.unread {
    background: rgba(229, 9, 20, 0.1);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: #e50914;
    border-radius: 50%;
}

.notification-title {
    color: white;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
}

.notification-message {
    color: #b3b3b3;
    font-size: 12px;
    line-height: 1.4;
    margin-bottom: 5px;
}

.notification-time {
    color: #666;
    font-size: 11px;
}

.notification-type {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
    margin-left: 10px;
}

.notification-type.info { background: #17a2b8; color: white; }
.notification-type.success { background: #28a745; color: white; }
.notification-type.warning { background: #ffc107; color: black; }
.notification-type.error { background: #dc3545; color: white; }

.loading-notifications {
    padding: 20px;
    text-align: center;
    color: #b3b3b3;
}

.no-notifications {
    padding: 20px;
    text-align: center;
    color: #b3b3b3;
    font-size: 14px;
}

.profile-menu {
    position: relative;
}

.profile-avatar {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: rgba(0,0,0,0.9);
    border: 1px solid #333;
    border-radius: 4px;
    min-width: 200px;
    display: none;
    z-index: 1001;
}

.profile-dropdown.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: white;
    text-decoration: none;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background: rgba(255,255,255,0.1);
}

.dropdown-item img {
    width: 24px;
    height: 24px;
    border-radius: 2px;
}

.dropdown-divider {
    height: 1px;
    background: #333;
    margin: 5px 0;
}

@media (max-width: 768px) {
    .netflix-header {
        padding: 0 20px;
    }
    
    .main-nav {
        display: none;
    }
    
    .search-input {
        width: 150px;
    }
    
    .notification-dropdown {
        width: 300px;
        right: -50px;
    }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
class UserNotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.init();
    }

    init() {
        this.loadNotifications();
        // Refresh notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }

    async loadNotifications() {
        try {
            const response = await fetch('api/get-user-notifications.php');
            const data = await response.json();
            
            if (data.notifications) {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.updateUI();
            }
        } catch (error) {
            console.error('[v0] Error loading notifications:', error);
        }
    }

    updateUI() {
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        
        // Update badge
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
        
        // Update notification list
        if (this.notifications.length === 0) {
            list.innerHTML = '<div class="no-notifications">No tienes notificaciones</div>';
        } else {
            list.innerHTML = this.notifications.map(notification => this.renderNotification(notification)).join('');
        }
    }

    renderNotification(notification) {
        const timeAgo = this.getTimeAgo(notification.received_at);
        const unreadClass = notification.is_read ? '' : 'unread';
        
        return `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notification.user_notification_id})">
                <div class="notification-title">
                    ${notification.title}
                    <span class="notification-type ${notification.type}">${notification.type}</span>
                </div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${timeAgo}</div>
            </div>
        `;
    }

    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Hace un momento';
        if (diffInSeconds < 3600) return `Hace ${Math.floor(diffInSeconds / 60)} min`;
        if (diffInSeconds < 86400) return `Hace ${Math.floor(diffInSeconds / 3600)} h`;
        return `Hace ${Math.floor(diffInSeconds / 86400)} días`;
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            if (response.ok) {
                // Update local state
                const notification = this.notifications.find(n => n.user_notification_id == notificationId);
                if (notification && !notification.is_read) {
                    notification.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateUI();
                }
            }
        } catch (error) {
            console.error('[v0] Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        const unreadNotifications = this.notifications.filter(n => !n.is_read);
        
        for (const notification of unreadNotifications) {
            await this.markAsRead(notification.user_notification_id);
        }
    }
}

// Initialize notification manager
let userNotificationManager;
document.addEventListener('DOMContentLoaded', () => {
    userNotificationManager = new UserNotificationManager();
});

function toggleNotificationMenu() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    // Close profile menu if open
    document.getElementById('profileDropdown').classList.remove('show');
}

function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
    
    // Close notification menu if open
    document.getElementById('notificationDropdown').classList.remove('show');
}

function markAsRead(notificationId) {
    if (userNotificationManager) {
        userNotificationManager.markAsRead(notificationId);
    }
}

function markAllAsRead() {
    if (userNotificationManager) {
        userNotificationManager.markAllAsRead();
    }
}

// Cerrar dropdowns al hacer click fuera
document.addEventListener('click', function(event) {
    const profileMenu = document.querySelector('.profile-menu');
    const notificationMenu = document.querySelector('.notification-menu');
    const profileDropdown = document.getElementById('profileDropdown');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (!profileMenu.contains(event.target)) {
        profileDropdown.classList.remove('show');
    }
    
    if (!notificationMenu.contains(event.target)) {
        notificationDropdown.classList.remove('show');
    }
});

// Cambiar fondo del header al hacer scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('.netflix-header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Funcionalidad de búsqueda
document.getElementById('search-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const query = this.value.trim();
        if (query) {
            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    }
});
</script>
