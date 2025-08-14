<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Netflix</title>
    <link rel="stylesheet" href="assets/css/netflix-auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="assets/images/netflix-logo.png" alt="Netflix" class="logo">
        </div>
        
        <div class="auth-form-container">
            <h1>Iniciar sesión</h1>
            
            <div id="error-message" class="error-message" style="display: none;"></div>
            
            <form id="loginForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Contraseña" required>
                </div>
                
                <button type="submit" class="auth-button" id="submitBtn">
                    <span class="button-text">Iniciar sesión</span>
                    <span class="loading-spinner" style="display: none;">⟳</span>
                </button>
            </form>
            
            <div class="auth-footer">
                <p>¿Nuevo en Netflix? <a href="register.php">Regístrate ahora</a></p>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const buttonText = submitBtn.querySelector('.button-text');
        const loadingSpinner = submitBtn.querySelector('.loading-spinner');
        const errorDiv = document.getElementById('error-message');
        
        // Mostrar loading
        submitBtn.disabled = true;
        buttonText.style.display = 'none';
        loadingSpinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';
        
        const formData = new FormData(this);
        
        fetch('login.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'profiles.php';
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.disabled = false;
            buttonText.style.display = 'inline-block';
            loadingSpinner.style.display = 'none';
        });
    });
    </script>
</body>
</html>
