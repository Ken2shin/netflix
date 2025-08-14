<nav class="netflix-nav">
    <div class="container-fluid">
        <div class="nav-content">
            <div class="nav-left">
                <a href="home" class="logo">
                    <h1>Netflix</h1>
                </a>
                <ul class="nav-menu">
                    <li><a href="home" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">Inicio</a></li>
                    <li><a href="search?type=series">Series</a></li>
                    <li><a href="search?type=movie">Películas</a></li>
                    <li><a href="my-list">Mi Lista</a></li>
                </ul>
            </div>
            
            <div class="nav-right">
                <div class="search-container">
                    <button class="search-toggle" onclick="toggleSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Títulos, personas, géneros">
                        <div id="search-suggestions" class="search-suggestions"></div>
                    </div>
                </div>
                
                <div class="profile-menu">
                    <div class="profile-dropdown">
                        <button class="profile-btn" onclick="toggleProfileMenu()">
                            <img src="<?php echo $_SESSION['profile_avatar'] ?? 'assets/images/default-avatar.png'; ?>" 
                                 alt="Profile" class="profile-avatar">
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profiles" class="dropdown-item">
                                <i class="fas fa-user"></i> Cambiar Perfil
                            </a>
                            <a href="account" class="dropdown-item">
                                <i class="fas fa-cog"></i> Cuenta
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.search-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.95);
  border-radius: 4px;
  max-height: 400px;
  overflow-y: auto;
  z-index: 1000;
  display: none;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 10px;
  cursor: pointer;
  border-bottom: 1px solid #333;
}

.suggestion-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.suggestion-poster {
  width: 40px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 10px;
}

.suggestion-info {
  flex: 1;
}

.suggestion-title {
  color: white;
  font-weight: 500;
  margin-bottom: 2px;
}

.suggestion-type {
  color: #999;
  font-size: 0.9rem;
}
</style>
