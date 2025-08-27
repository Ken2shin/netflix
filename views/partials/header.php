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
}
</style>

<script>
function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Cerrar dropdown al hacer click fuera
document.addEventListener('click', function(event) {
    const profileMenu = document.querySelector('.profile-menu');
    const dropdown = document.getElementById('profileDropdown');
    
    if (!profileMenu.contains(event.target)) {
        dropdown.classList.remove('show');
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
