<div class="admin-header">
    <div class="header-left">
        <button class="btn btn-link sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="header-right">
        <div class="admin-user">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
    </div>
</div>
