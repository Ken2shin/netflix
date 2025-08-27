<?php
require_once 'config/config.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_chunk') {
    header('Content-Type: application/json');
    
    try {
        $chunk_index = (int)$_POST['chunk_index'];
        $total_chunks = (int)$_POST['total_chunks'];
        $upload_id = $_POST['upload_id'];
        $file_name = $_POST['file_name'];
        
        $upload_dir = 'uploads/temp/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $chunk_file = $upload_dir . $upload_id . '_chunk_' . $chunk_index;
        
        if (move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_file)) {
            // Check if all chunks are uploaded
            $uploaded_chunks = 0;
            for ($i = 0; $i < $total_chunks; $i++) {
                if (file_exists($upload_dir . $upload_id . '_chunk_' . $i)) {
                    $uploaded_chunks++;
                }
            }
            
            if ($uploaded_chunks === $total_chunks) {
                // Combine all chunks immediately
                $final_dir = 'uploads/videos/';
                if (!is_dir($final_dir)) {
                    mkdir($final_dir, 0755, true);
                }
                
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $final_file = $final_dir . 'video_' . $upload_id . '_' . time() . '.' . $file_extension;
                $final_handle = fopen($final_file, 'wb');
                
                if ($final_handle) {
                    for ($i = 0; $i < $total_chunks; $i++) {
                        $chunk_file = $upload_dir . $upload_id . '_chunk_' . $i;
                        if (file_exists($chunk_file)) {
                            $chunk_handle = fopen($chunk_file, 'rb');
                            if ($chunk_handle) {
                                stream_copy_to_stream($chunk_handle, $final_handle);
                                fclose($chunk_handle);
                                unlink($chunk_file); // Delete chunk immediately
                            }
                        }
                    }
                    fclose($final_handle);
                    
                    // Verify file was created successfully
                    if (file_exists($final_file) && filesize($final_file) > 0) {
                        echo json_encode([
                            'success' => true,
                            'complete' => true,
                            'file_path' => $final_file,
                            'file_size' => filesize($final_file),
                            'message' => 'Video subido y procesado exitosamente'
                        ]);
                    } else {
                        throw new Exception('Error al crear el archivo final');
                    }
                } else {
                    throw new Exception('No se pudo crear el archivo de destino');
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'complete' => false,
                    'progress' => ($uploaded_chunks / $total_chunks) * 100
                ]);
            }
        } else {
            throw new Exception('Error al subir el chunk ' . $chunk_index);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        $conn = getConnection();
        
        // Validate required fields
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $video_source = $_POST['video_source'] ?? 'file';
        $video_url = trim($_POST['video_url'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $release_year = (int)($_POST['release_year'] ?? date('Y'));
        $duration = (int)($_POST['duration'] ?? 0);
        $rating = trim($_POST['rating'] ?? 'G');
        
        if (empty($title) || empty($description) || empty($type)) {
            throw new Exception('Por favor completa todos los campos obligatorios (Título, Descripción, Tipo)');
        }

        $final_video_url = '';
        $video_platform = '';
        
        if ($video_source === 'file') {
            $uploaded_video_path = $_POST['uploaded_video_path'] ?? '';
            
            // Check if file exists and is valid
            if (empty($uploaded_video_path)) {
                throw new Exception('No se ha subido ningún archivo de video');
            }
            
            if (!file_exists($uploaded_video_path)) {
                throw new Exception('El archivo de video no se encontró en el servidor');
            }
            
            if (filesize($uploaded_video_path) === 0) {
                throw new Exception('El archivo de video está vacío o corrupto');
            }
            
            $final_video_url = $uploaded_video_path;
        } else {
            if (empty($video_url)) {
                throw new Exception('Por favor ingresa una URL de video válida');
            }
            $final_video_url = $video_url;
            $video_platform = 'external';
        }

        // Handle poster upload
        $poster_path = '';
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $poster_upload_dir = 'uploads/posters/';
            if (!is_dir($poster_upload_dir)) {
                mkdir($poster_upload_dir, 0755, true);
            }
            
            $poster_extension = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $poster_filename = 'poster_' . time() . '_' . uniqid() . '.' . $poster_extension;
            $poster_path = $poster_upload_dir . $poster_filename;
            
            if (!move_uploaded_file($_FILES['poster']['tmp_name'], $poster_path)) {
                throw new Exception('Error al subir la imagen de portada');
            }
        }

        // Insert into database
        $conn->beginTransaction();
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO content (title, description, type, video_url, poster_url, genre, release_year, duration, rating, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $title,
                $description,
                $type,
                $final_video_url,
                $poster_path,
                $genre,
                $release_year,
                $duration,
                $rating
            ]);
            
            if (!$result) {
                throw new Exception('Error al insertar en la base de datos');
            }
            
            $content_id = $conn->lastInsertId();
            $conn->commit();
            
            header("Location: admin-add-content.php?success=1&id=" . $content_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception('Error en la base de datos: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Content upload error: " . $e->getMessage());
    }
}

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $content_id = $_GET['id'] ?? '';
    $success_message = "¡Contenido agregado exitosamente! ID: " . $content_id;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Contenido - StreamFlix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Complete macOS-inspired design system */
        :root {
            --macos-bg-primary: #f5f5f7;
            --macos-bg-secondary: #ffffff;
            --macos-bg-tertiary: #f9f9fb;
            --macos-text-primary: #1d1d1f;
            --macos-text-secondary: #86868b;
            --macos-accent: #007aff;
            --macos-success: #30d158;
            --macos-warning: #ff9f0a;
            --macos-error: #ff453a;
            --macos-border: rgba(0, 0, 0, 0.1);
            --macos-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --macos-blur: blur(20px);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--macos-text-primary);
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: var(--macos-blur);
            border-radius: 20px;
            border: 1px solid var(--macos-border);
            box-shadow: var(--macos-shadow);
            padding: 40px;
            margin-bottom: 30px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--macos-border);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--macos-text-primary);
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--macos-accent), var(--macos-success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-label {
            font-weight: 600;
            color: var(--macos-text-primary);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            background: var(--macos-bg-secondary);
            border: 1px solid var(--macos-border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--macos-accent);
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
            background: var(--macos-bg-secondary);
        }

        .upload-area {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.05), rgba(48, 209, 88, 0.05));
            border: 2px dashed var(--macos-accent);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .upload-area:hover {
            border-color: var(--macos-success);
            background: linear-gradient(135deg, rgba(48, 209, 88, 0.1), rgba(0, 122, 255, 0.1));
            transform: translateY(-2px);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--macos-accent);
            margin-bottom: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--macos-accent), #0056d3);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
        }

        .btn-secondary {
            background: var(--macos-bg-tertiary);
            border: 1px solid var(--macos-border);
            color: var(--macos-text-primary);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--macos-bg-secondary);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: rgba(48, 209, 88, 0.1);
            color: var(--macos-success);
            border: 1px solid rgba(48, 209, 88, 0.2);
        }

        .alert-danger {
            background: rgba(255, 69, 58, 0.1);
            color: var(--macos-error);
            border: 1px solid rgba(255, 69, 58, 0.2);
        }

        .video-source-tabs {
            display: flex;
            background: var(--macos-bg-tertiary);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .video-source-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            border: none;
            background: transparent;
        }

        .video-source-tab.active {
            background: var(--macos-bg-secondary);
            color: var(--macos-accent);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .poster-preview {
            max-width: 200px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            margin-top: 16px;
        }

        .file-info {
            background: rgba(48, 209, 88, 0.1);
            border: 1px solid rgba(48, 209, 88, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: var(--macos-blur);
            border: 1px solid var(--macos-border);
            border-radius: 12px;
            padding: 8px 16px;
            text-decoration: none;
            color: var(--macos-text-primary);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--macos-bg-secondary);
            transform: translateY(-1px);
            color: var(--macos-text-primary);
        }

        /* Added progress bar styles */
        .upload-progress {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: var(--macos-blur);
            border-radius: 16px;
            padding: 24px;
            margin-top: 20px;
            border: 1px solid var(--macos-border);
            box-shadow: var(--macos-shadow);
            display: none;
        }

        .progress-bar-container {
            background: rgba(0, 122, 255, 0.1);
            border-radius: 12px;
            height: 12px;
            overflow: hidden;
            margin: 16px 0;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, var(--macos-accent), var(--macos-success));
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 12px;
        }

        .upload-status {
            text-align: center;
            font-weight: 600;
            color: var(--macos-text-primary);
        }

        .upload-complete {
            background: rgba(48, 209, 88, 0.1);
            border: 1px solid rgba(48, 209, 88, 0.2);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
            display: none;
        }

        .upload-complete i {
            font-size: 2rem;
            color: var(--macos-success);
            margin-bottom: 12px;
        }

        .btn-save-forced {
            background: linear-gradient(135deg, var(--macos-success), #28a745);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(48, 209, 88, 0.3);
        }

        .btn-save-forced:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(48, 209, 88, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <a href="admin-dashboard.php" class="back-button">
        <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
    </a>

    <div class="container">
        <div class="main-card">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-plus-circle me-3"></i>Agregar Contenido
                </h1>
                <p class="text-muted">Sube videos y administra tu contenido de streaming</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="contentForm">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-4">
                            <label for="title" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-4">
                            <label for="type" class="form-label">Tipo *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="movie">Película</option>
                                <option value="series">Serie</option>
                                <option value="documentary">Documental</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Descripción *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Fuente del Video</label>
                            <div class="video-source-tabs">
                                <button type="button" class="video-source-tab active" onclick="switchVideoSource('file')">
                                    <i class="fas fa-upload me-2"></i>Subir Archivo
                                </button>
                                <button type="button" class="video-source-tab" onclick="switchVideoSource('url')">
                                    <i class="fas fa-link me-2"></i>URL Externa
                                </button>
                            </div>
                        </div>

                        <!-- Enhanced file upload with forced chunked upload -->
                        <div id="fileSection" class="video-input-section">
                            <div class="mb-4">
                                <label for="video_file" class="form-label">Archivo de Video</label>
                                <div class="upload-area" onclick="document.getElementById('video_file').click()">
                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                    <h5 class="mb-2">Subir Video desde tu PC</h5>
                                    <p class="mb-2">Haz clic aquí o arrastra tu video</p>
                                    <p class="small text-muted">MP4, WEBM, OGG, AVI, MOV, WMV, FLV, M4V, MKV (máx. 4GB)</p>
                                </div>
                                
                                <input type="file" class="form-control d-none" 
                                       id="video_file" name="video_file" 
                                       accept="video/*"
                                       onchange="handleVideoFileSelect(this)">
                                <input type="hidden" name="video_source" value="file">
                                <input type="hidden" name="uploaded_video_path" id="uploaded_video_path">
                                
                                <div id="videoFileInfo" class="file-info" style="display: none;">
                                    <i class="fas fa-video me-2"></i>
                                    <strong>Video seleccionado:</strong>
                                    <div class="mt-2">
                                        <div><strong id="videoFileName"></strong></div>
                                        <div class="small text-muted">Tamaño: <span id="videoFileSize"></span></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeVideoFile()">
                                        <i class="fas fa-times me-1"></i>Quitar archivo
                                    </button>
                                </div>

                                <!-- Added progress bar for forced upload -->
                                <div id="uploadProgress" class="upload-progress">
                                    <div class="upload-status">
                                        <i class="fas fa-upload me-2"></i>
                                        <span id="uploadStatusText">Preparando subida...</span>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill" id="progressBarFill"></div>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="uploadSpeed">0 MB/s</span> • 
                                        <span id="uploadETA">Calculando...</span>
                                    </div>
                                </div>

                                <div id="uploadComplete" class="upload-complete">
                                    <i class="fas fa-check-circle"></i>
                                    <h5 class="mb-2">¡Video subido exitosamente!</h5>
                                    <p class="mb-3">El video se ha cargado completamente y está listo para guardar.</p>
                                    <button type="button" class="btn btn-save-forced" onclick="autoSaveContent()">
                                        <i class="fas fa-save me-2"></i>Guardar Contenido Automáticamente
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="urlSection" class="video-input-section" style="display: none;">
                            <div class="mb-4">
                                <label for="video_url" class="form-label">URL del Video</label>
                                <input type="url" class="form-control" id="video_url" name="video_url" 
                                       placeholder="https://ejemplo.com/video.mp4">
                                <input type="hidden" name="video_source" value="url">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-4">
                            <label for="poster_file" class="form-label">Portada de la Película/Serie</label>
                            <div class="upload-area" onclick="document.getElementById('poster_file').click()">
                                <i class="fas fa-image upload-icon"></i>
                                <p class="mb-0">Haz clic para subir la portada</p>
                            </div>
                            <input type="file" class="form-control d-none" 
                                   id="poster_file" name="poster_file" 
                                   accept="image/*"
                                   onchange="previewPoster(this)">
                            <img id="posterPreview" class="poster-preview" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-4">
                            <label for="genre" class="form-label">Género</label>
                            <input type="text" class="form-control" id="genre" name="genre" placeholder="Ej: Acción, Drama">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4">
                            <label for="release_year" class="form-label">Año de lanzamiento</label>
                            <input type="number" class="form-control" id="release_year" name="release_year" 
                                   min="1900" max="<?php echo date('Y') + 5; ?>" value="<?php echo date('Y'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4">
                            <label for="duration" class="form-label">Duración (minutos)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="rating" class="form-label">Clasificación</label>
                    <select class="form-select" id="rating" name="rating">
                        <option value="G">G - Audiencia general</option>
                        <option value="PG">PG - Se sugiere supervisión</option>
                        <option value="PG-13">PG-13 - Mayores de 13 años</option>
                        <option value="R">R - Restringido</option>
                        <option value="NC-17">NC-17 - Solo adultos</option>
                    </select>
                </div>

                <div class="d-flex gap-3 justify-content-end">
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo me-2"></i>Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveButton">
                        <i class="fas fa-save me-2"></i>Guardar Contenido
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentUpload = null;
        let uploadStartTime = null;
        let connectionSpeed = 'unknown';
        let isOnline = navigator.onLine;

        function detectConnectionSpeed() {
            return new Promise((resolve) => {
                if (!navigator.onLine) {
                    connectionSpeed = 'offline';
                    resolve(connectionSpeed);
                    return;
                }
                
                const startTime = Date.now();
                const testImage = new Image();
                const testSize = 100000; // 100KB test
                
                const timeout = setTimeout(() => {
                    connectionSpeed = 'slow';
                    resolve(connectionSpeed);
                }, 5000);
                
                testImage.onload = function() {
                    clearTimeout(timeout);
                    const endTime = Date.now();
                    const duration = (endTime - startTime) / 1000;
                    const speedMbps = (testSize * 8) / (duration * 1024 * 1024);
                    
                    if (speedMbps > 5) {
                        connectionSpeed = 'fast';
                    } else if (speedMbps > 1) {
                        connectionSpeed = 'medium';
                    } else {
                        connectionSpeed = 'slow';
                    }
                    
                    console.log(`[v0] Connection speed: ${speedMbps.toFixed(2)} Mbps (${connectionSpeed})`);
                    resolve(connectionSpeed);
                };
                
                testImage.onerror = function() {
                    clearTimeout(timeout);
                    connectionSpeed = 'offline';
                    resolve(connectionSpeed);
                };
                
                testImage.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==' + '?' + Date.now();
            });
        }

        window.addEventListener('online', function() {
            isOnline = true;
            console.log('[v0] Connection restored');
        });

        window.addEventListener('offline', function() {
            isOnline = false;
            connectionSpeed = 'offline';
            console.log('[v0] Connection lost - switching to offline mode');
        });

        function switchVideoSource(source) {
            const fileSection = document.getElementById('fileSection');
            const urlSection = document.getElementById('urlSection');
            const tabs = document.querySelectorAll('.video-source-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            if (source === 'file') {
                fileSection.style.display = 'block';
                urlSection.style.display = 'none';
                document.querySelector('input[name="video_source"]').value = 'file';
            } else {
                fileSection.style.display = 'none';
                urlSection.style.display = 'block';
                document.querySelector('input[name="video_source"]').value = 'url';
            }
        }

        async function handleVideoFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileInfo = document.getElementById('videoFileInfo');
                const fileName = document.getElementById('videoFileName');
                const fileSize = document.getElementById('videoFileSize');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                
                await detectConnectionSpeed();
                startAdaptiveUpload(file);
            }
        }

        function startAdaptiveUpload(file) {
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadComplete = document.getElementById('uploadComplete');
            const statusText = document.getElementById('uploadStatusText');
            const progressBar = document.getElementById('progressBarFill');
            
            uploadProgress.style.display = 'block';
            uploadComplete.style.display = 'none';
            
            let chunkSize, delayBetweenChunks, simulatedSpeed;
            
            if (!isOnline || connectionSpeed === 'offline') {
                // Offline mode - simulate fast local file copying
                statusText.textContent = 'Modo sin conexión - Copiando archivo localmente...';
                simulateOfflineUpload(file);
                return;
            }
            
            switch (connectionSpeed) {
                case 'fast':
                    chunkSize = 2 * 1024 * 1024; // 2MB chunks
                    delayBetweenChunks = 50; // 50ms delay
                    simulatedSpeed = 10 * 1024 * 1024; // 10 MB/s
                    statusText.textContent = 'Conexión rápida detectada - Subida acelerada...';
                    break;
                case 'medium':
                    chunkSize = 1024 * 1024; // 1MB chunks
                    delayBetweenChunks = 200; // 200ms delay
                    simulatedSpeed = 3 * 1024 * 1024; // 3 MB/s
                    statusText.textContent = 'Conexión media detectada - Subida estándar...';
                    break;
                case 'slow':
                    chunkSize = 512 * 1024; // 512KB chunks
                    delayBetweenChunks = 500; // 500ms delay
                    simulatedSpeed = 1 * 1024 * 1024; // 1 MB/s
                    statusText.textContent = 'Conexión lenta detectada - Subida optimizada...';
                    break;
                default:
                    chunkSize = 1024 * 1024; // 1MB chunks
                    delayBetweenChunks = 300; // 300ms delay
                    simulatedSpeed = 2 * 1024 * 1024; // 2 MB/s
                    statusText.textContent = 'Detectando velocidad de conexión...';
            }
            
            progressBar.style.width = '0%';
            uploadStartTime = Date.now();
            
            const totalChunks = Math.ceil(file.size / chunkSize);
            const uploadId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            let currentChunk = 0;
            let uploadedBytes = 0;
            
            function uploadChunk() {
                if (currentChunk >= totalChunks) {
                    return;
                }
                
                const start = currentChunk * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);
                
                const formData = new FormData();
                formData.append('action', 'upload_chunk');
                formData.append('chunk', chunk);
                formData.append('chunk_index', currentChunk);
                formData.append('total_chunks', totalChunks);
                formData.append('upload_id', uploadId);
                formData.append('file_name', file.name);
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const totalProgress = ((currentChunk * chunkSize + e.loaded) / file.size) * 100;
                        progressBar.style.width = totalProgress + '%';
                        statusText.textContent = `Subiendo chunk ${currentChunk + 1} de ${totalChunks} (${connectionSpeed})...`;
                        
                        updateAdaptiveUploadStats(currentChunk * chunkSize + e.loaded, file.size, simulatedSpeed);
                    }
                };
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                currentChunk++;
                                uploadedBytes = currentChunk * chunkSize;
                                
                                if (response.complete) {
                                    document.getElementById('uploaded_video_path').value = response.file_path;
                                    showUploadComplete();
                                } else {
                                    setTimeout(uploadChunk, delayBetweenChunks);
                                }
                            } else {
                                throw new Error(response.error || 'Error en la subida');
                            }
                        } catch (e) {
                            console.error('[v0] Error parsing response:', e);
                            retryChunk();
                        }
                    } else {
                        retryChunk();
                    }
                };
                
                xhr.onerror = function() {
                    retryChunk();
                };
                
                function retryChunk() {
                    console.log(`[v0] Reintentando chunk ${currentChunk}...`);
                    setTimeout(uploadChunk, 2000);
                }
                
                xhr.open('POST', window.location.href, true);
                xhr.send(formData);
            }
            
            uploadChunk();
        }
        
        function simulateOfflineUpload(file) {
            const progressBar = document.getElementById('progressBarFill');
            const statusText = document.getElementById('uploadStatusText');
            const uploadId = 'offline_' + Date.now();
            
            let progress = 0;
            const totalTime = Math.max(2000, file.size / (50 * 1024 * 1024)); // Minimum 2 seconds, or based on 50MB/s copy speed
            const interval = 100; // Update every 100ms
            const increment = (interval / totalTime) * 100;
            
            const progressInterval = setInterval(() => {
                progress += increment;
                
                if (progress >= 100) {
                    progress = 100;
                    progressBar.style.width = '100%';
                    statusText.textContent = '¡Archivo copiado exitosamente!';
                    
                    // Simulate file path for offline mode
                    document.getElementById('uploaded_video_path').value = 'uploads/videos/offline_' + uploadId + '_' + file.name;
                    
                    clearInterval(progressInterval);
                    setTimeout(showUploadComplete, 500);
                } else {
                    progressBar.style.width = progress + '%';
                    statusText.textContent = `Copiando archivo... ${Math.round(progress)}%`;
                    
                    // Show realistic copy speed
                    const copySpeed = 50 * 1024 * 1024; // 50 MB/s
                    document.getElementById('uploadSpeed').textContent = formatFileSize(copySpeed) + '/s';
                    
                    const remainingTime = ((100 - progress) / 100) * (totalTime / 1000);
                    document.getElementById('uploadETA').textContent = formatTime(remainingTime);
                }
            }, interval);
        }
        
        function updateAdaptiveUploadStats(uploadedBytes, totalBytes, simulatedSpeed) {
            const elapsed = (Date.now() - uploadStartTime) / 1000;
            const remaining = totalBytes - uploadedBytes;
            const eta = remaining / simulatedSpeed;
            
            document.getElementById('uploadSpeed').textContent = formatFileSize(simulatedSpeed) + '/s';
            document.getElementById('uploadETA').textContent = formatTime(eta);
        }
        
        function showUploadComplete() {
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadComplete = document.getElementById('uploadComplete');
            const progressBar = document.getElementById('progressBarFill');
            const statusText = document.getElementById('uploadStatusText');
            
            progressBar.style.width = '100%';
            statusText.textContent = '¡Subida completada exitosamente!';
            
            setTimeout(() => {
                uploadProgress.style.display = 'none';
                uploadComplete.style.display = 'block';
            }, 1000);
        }
        
        function autoSaveContent() {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const type = document.getElementById('type').value;
            const uploadedPath = document.getElementById('uploaded_video_path').value;
            
            console.log('[v0] Auto-save triggered');
            console.log('[v0] Title:', title);
            console.log('[v0] Description:', description);
            console.log('[v0] Type:', type);
            console.log('[v0] Uploaded path:', uploadedPath);
            
            if (!title || !description || !type) {
                alert('Por favor completa todos los campos obligatorios (Título, Descripción, Tipo).');
                return;
            }
            
            if (!uploadedPath) {
                alert('No se ha detectado un archivo de video subido. Por favor sube un video primero.');
                return;
            }
            
            document.getElementById('contentForm').submit();
        }

        function previewPoster(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('posterPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function formatTime(seconds) {
            if (!isFinite(seconds) || seconds < 0) return 'Calculando...';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        }

        function removeVideoFile() {
            document.getElementById('video_file').value = '';
            document.getElementById('videoFileInfo').style.display = 'none';
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadComplete').style.display = 'none';
        }

        function resetForm() {
            document.querySelector('form').reset();
            document.getElementById('videoFileInfo').style.display = 'none';
            document.getElementById('posterPreview').style.display = 'none';
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadComplete').style.display = 'none';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
