<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - StreamFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/netflix.css">
</head>
<body>
    <div class="error-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <h1 class="error-code">404</h1>
                    <h2 class="error-title">Página no encontrada</h2>
                    <p class="error-description">Lo sentimos, la página que buscas no existe.</p>
                    <a href="/" class="btn btn-danger">Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .error-page {
            background-color: #141414;
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #E50914;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .error-description {
            font-size: 1.2rem;
            color: #999;
            margin-bottom: 30px;
        }
    </style>
</body>
</html>
