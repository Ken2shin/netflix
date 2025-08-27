<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 0);
ini_set('max_input_time', 0);
set_time_limit(0);

echo "[v0] Starting production WebSocket server initialization...\n";

$autoloaderPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php'
];

$autoloaderFound = false;
foreach ($autoloaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderFound = true;
        echo "[v0] Autoloader found at: $path\n";
        break;
    }
}

if (!$autoloaderFound) {
    echo "Error: Composer autoloader no encontrado en ninguna ubicación.\n";
    echo "Ubicaciones verificadas:\n";
    foreach ($autoloaderPaths as $path) {
        echo "  - $path\n";
    }
    echo "Ejecuta 'composer install' desde el directorio raíz del proyecto.\n";
    exit(1);
}

try {
    $configPaths = [
        __DIR__ . '/../config/config.php',
        __DIR__ . '/../config/database.php'
    ];
    
    foreach ($configPaths as $configPath) {
        if (file_exists($configPath)) {
            require_once $configPath;
            echo "[v0] Config loaded: $configPath\n";
        } else {
            echo "[v0] Warning: Config file not found: $configPath\n";
        }
    }
} catch (Exception $e) {
    echo "[v0] Warning: Error loading config: {$e->getMessage()}\n";
}

if (!class_exists('Ratchet\Server\IoServer')) {
    echo "Error: Ratchet WebSocket library no encontrada.\n";
    echo "Instalando dependencias automáticamente...\n";
    
    $composerCommands = [
        'cd .. && composer require ratchet/pawl ratchet/socket-io ratchet/rfc6455',
        'cd .. && composer install --no-dev --optimize-autoloader'
    ];
    
    foreach ($composerCommands as $command) {
        echo "[v0] Ejecutando: $command\n";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "Error ejecutando comando. Output:\n";
            echo implode("\n", $output) . "\n";
        }
    }
    
    // Try to reload autoloader
    foreach ($autoloaderPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    if (!class_exists('Ratchet\Server\IoServer')) {
        echo "Error: No se pudo instalar Ratchet. Instala manualmente con:\n";
        echo "composer require ratchet/pawl ratchet/socket-io ratchet/rfc6455\n";
        exit(1);
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ProductionVideoUploadServer implements MessageComponentInterface {
    protected $clients;
    protected $uploads;
    protected $maxConcurrentUploads = 10;
    protected $maxUploadSize = 2147483648; // 2GB limit
    protected $chunkSize = 1048576; // 1MB chunks
    protected $activeUploads = 0;
    protected $uploadQueue = [];
    protected $rateLimiter = [];
    protected $lastCleanup = 0;
    protected $dbConnection;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->uploads = [];
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $this->dbConnection = getConnection();
            echo "[v0] Database connection established for video storage\n";
        } catch (Exception $e) {
            throw new Exception("Failed to connect to database: " . $e->getMessage());
        }
        
        echo "[v0] Production VideoUploadServer initialized with database storage\n";
        echo "[v0] Max concurrent uploads: {$this->maxConcurrentUploads}\n";
        echo "[v0] Max upload size: " . $this->formatBytes($this->maxUploadSize) . "\n";
        echo "[v0] Video storage: Database (LONGBLOB)\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $clientIp = $this->getClientIp($conn);
        
        if (!isset($this->rateLimiter[$clientIp])) {
            $this->rateLimiter[$clientIp] = [
                'requests' => 0,
                'lastRequest' => time(),
                'uploads' => 0
            ];
        }
        
        echo "[v0] Nueva conexión WebSocket: ({$conn->resourceId}) from {$clientIp}\n";
        
        $conn->send(json_encode([
            'type' => 'connection_established',
            'message' => 'Conectado al servidor de uploads',
            'clientId' => $conn->resourceId,
            'maxUploadSize' => $this->maxUploadSize,
            'chunkSize' => $this->chunkSize,
            'serverStatus' => [
                'activeUploads' => $this->activeUploads,
                'maxConcurrent' => $this->maxConcurrentUploads,
                'memoryUsage' => memory_get_usage(true),
                'memoryLimit' => ini_get('memory_limit')
            ]
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $clientIp = $this->getClientIp($from);
        
        if (!$this->checkRateLimit($clientIp)) {
            $from->send(json_encode([
                'type' => 'rate_limit_exceeded',
                'message' => 'Demasiadas solicitudes. Espera un momento.'
            ]));
            return;
        }
        
        if (time() - $this->lastCleanup > 300) { // Every 5 minutes
            $this->cleanupStaleUploads();
            $this->lastCleanup = time();
        }
        
        try {
            $data = json_decode($msg, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON data');
            }
            
            switch ($data['type']) {
                case 'ping':
                    $from->send(json_encode([
                        'type' => 'pong',
                        'serverTime' => time(),
                        'memoryUsage' => memory_get_usage(true)
                    ]));
                    break;
                case 'upload_start':
                    $this->handleUploadStart($from, $data);
                    break;
                case 'upload_chunk':
                    $this->handleUploadChunk($from, $data);
                    break;
                case 'upload_complete':
                    $this->handleUploadComplete($from, $data);
                    break;
                case 'upload_cancel':
                    $this->handleUploadCancel($from, $data);
                    break;
                default:
                    echo "[v0] Tipo de mensaje desconocido: {$data['type']}\n";
            }
        } catch (Exception $e) {
            echo "[v0] Error procesando mensaje: {$e->getMessage()}\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error procesando mensaje: ' . $e->getMessage()
            ]));
        }
        
        if (memory_get_usage(true) > (256 * 1024 * 1024 * 0.8)) { // 80% of 256MB
            gc_collect_cycles();
            echo "[v0] Garbage collection executed. Memory: " . $this->formatBytes(memory_get_usage(true)) . "\n";
        }
    }

    private function handleUploadStart($conn, $data) {
        $clientIp = $this->getClientIp($conn);
        
        if ($this->activeUploads >= $this->maxConcurrentUploads) {
            $conn->send(json_encode([
                'type' => 'upload_queued',
                'message' => 'Servidor ocupado. Tu upload está en cola.',
                'queuePosition' => count($this->uploadQueue) + 1
            ]));
            
            $this->uploadQueue[] = [
                'conn' => $conn,
                'data' => $data,
                'timestamp' => time()
            ];
            return;
        }
        
        $filesize = $data['filesize'] ?? 0;
        if ($filesize > $this->maxUploadSize) {
            $conn->send(json_encode([
                'type' => 'upload_error',
                'message' => 'Archivo demasiado grande. Máximo: ' . $this->formatBytes($this->maxUploadSize)
            ]));
            return;
        }
        
        $uploadId = uniqid('upload_', true);
        
        try {
            $stmt = $this->dbConnection->prepare("DELETE FROM video_chunks WHERE upload_id = ?");
            $stmt->execute([$uploadId]);
        } catch (Exception $e) {
            echo "[v0] Warning: Could not clean existing chunks: {$e->getMessage()}\n";
        }
        
        $this->uploads[$uploadId] = [
            'conn' => $conn,
            'clientIp' => $clientIp,
            'filename' => $data['filename'] ?? 'unknown_file',
            'filesize' => $filesize,
            'uploaded' => 0,
            'startTime' => microtime(true),
            'lastActivity' => time(),
            'expectedChunks' => ceil($filesize / $this->chunkSize),
            'receivedChunks' => 0,
            'chunkMap' => [] // Track which chunks we've received
        ];
        
        $this->activeUploads++;
        $this->rateLimiter[$clientIp]['uploads']++;

        echo "[v0] Iniciando upload: $uploadId - {$this->uploads[$uploadId]['filename']} ({$this->formatBytes($filesize)})\n";
        echo "[v0] Active uploads: {$this->activeUploads}/{$this->maxConcurrentUploads}\n";

        $conn->send(json_encode([
            'type' => 'upload_ready',
            'uploadId' => $uploadId,
            'message' => 'Listo para recibir chunks',
            'chunkSize' => $this->chunkSize,
            'expectedChunks' => $this->uploads[$uploadId]['expectedChunks']
        ]));
    }

    private function handleUploadChunk($conn, $data) {
        $uploadId = $data['uploadId'];
        $chunkIndex = $data['chunkIndex'];
        
        if (!isset($this->uploads[$uploadId])) {
            $conn->send(json_encode([
                'type' => 'upload_error',
                'message' => 'Upload ID no válido'
            ]));
            return;
        }
        
        $upload = &$this->uploads[$uploadId];
        $upload['lastActivity'] = time();
        
        if ($chunkIndex < 0 || $chunkIndex >= $upload['expectedChunks']) {
            $conn->send(json_encode([
                'type' => 'chunk_error',
                'message' => 'Índice de chunk inválido',
                'chunkIndex' => $chunkIndex
            ]));
            return;
        }
        
        if (isset($upload['chunkMap'][$chunkIndex])) {
            $conn->send(json_encode([
                'type' => 'chunk_duplicate',
                'chunkIndex' => $chunkIndex,
                'message' => 'Chunk ya recibido'
            ]));
            return;
        }
        
        $chunkData = base64_decode($data['data']);
        if ($chunkData === false) {
            $conn->send(json_encode([
                'type' => 'chunk_error',
                'message' => 'Error decodificando chunk',
                'chunkIndex' => $chunkIndex
            ]));
            return;
        }
        
        try {
            $stmt = $this->dbConnection->prepare("
                INSERT INTO video_chunks (upload_id, chunk_index, chunk_data, chunk_size) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                chunk_data = VALUES(chunk_data), 
                chunk_size = VALUES(chunk_size)
            ");
            $stmt->execute([$uploadId, $chunkIndex, $chunkData, strlen($chunkData)]);
            
            $upload['chunkMap'][$chunkIndex] = true;
            $upload['receivedChunks']++;
            $upload['uploaded'] += strlen($chunkData);
            
        } catch (Exception $e) {
            $conn->send(json_encode([
                'type' => 'chunk_error',
                'message' => 'Error almacenando chunk en base de datos',
                'chunkIndex' => $chunkIndex
            ]));
            echo "[v0] Database error storing chunk: {$e->getMessage()}\n";
            return;
        }
        
        $progress = ($upload['uploaded'] / $upload['filesize']) * 100;
        $elapsed = microtime(true) - $upload['startTime'];
        $speed = $elapsed > 0 ? ($upload['uploaded'] / $elapsed) : 0;
        $eta = $speed > 0 ? (($upload['filesize'] - $upload['uploaded']) / $speed) : 0;
        
        if ($upload['receivedChunks'] % 10 === 0 || $progress >= (floor($progress / 5) * 5)) {
            $conn->send(json_encode([
                'type' => 'upload_progress',
                'uploadId' => $uploadId,
                'progress' => round($progress, 2),
                'uploaded' => $upload['uploaded'],
                'total' => $upload['filesize'],
                'speed' => round($speed / 1024, 2), // KB/s
                'eta' => round($eta),
                'chunksReceived' => $upload['receivedChunks'],
                'chunksTotal' => $upload['expectedChunks'],
                'memoryUsage' => memory_get_usage(true)
            ]));
        }
        
        echo "[v0] Upload progress: {$uploadId} - " . round($progress, 1) . "% ({$upload['receivedChunks']}/{$upload['expectedChunks']} chunks)\n";
        
        unset($chunkData);
        unset($data['data']);
    }

    private function handleUploadComplete($conn, $data) {
        $uploadId = $data['uploadId'];
        
        if (!isset($this->uploads[$uploadId])) {
            $conn->send(json_encode([
                'type' => 'upload_error',
                'message' => 'Upload ID no válido'
            ]));
            return;
        }
        
        $upload = $this->uploads[$uploadId];
        
        if ($upload['receivedChunks'] !== $upload['expectedChunks']) {
            $missingChunks = [];
            for ($i = 0; $i < $upload['expectedChunks']; $i++) {
                if (!isset($upload['chunkMap'][$i])) {
                    $missingChunks[] = $i;
                }
            }
            
            $conn->send(json_encode([
                'type' => 'upload_incomplete',
                'message' => 'Faltan chunks',
                'missingChunks' => array_slice($missingChunks, 0, 10),
                'totalMissing' => count($missingChunks)
            ]));
            return;
        }
        
        try {
            echo "[v0] Assembling file from database chunks...\n";
            
            // Get all chunks ordered by index
            $stmt = $this->dbConnection->prepare("
                SELECT chunk_data, chunk_size 
                FROM video_chunks 
                WHERE upload_id = ? 
                ORDER BY chunk_index ASC
            ");
            $stmt->execute([$uploadId]);
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($chunks) !== $upload['expectedChunks']) {
                throw new Exception("Chunk count mismatch in database");
            }
            
            // Assemble complete file data
            $completeFileData = '';
            $totalSize = 0;
            
            foreach ($chunks as $chunk) {
                $completeFileData .= $chunk['chunk_data'];
                $totalSize += $chunk['chunk_size'];
            }
            
            if ($totalSize !== $upload['filesize']) {
                throw new Exception("File size mismatch: expected {$upload['filesize']}, got {$totalSize}");
            }
            
            // Generate unique filename and calculate checksum
            $extension = pathinfo($upload['filename'], PATHINFO_EXTENSION);
            $filename = 'video_' . uniqid() . '_' . time() . '.' . $extension;
            $checksum = hash('sha256', $completeFileData);
            $mimeType = $this->getMimeType($extension);
            
            // Store complete file in video_files table
            $stmt = $this->dbConnection->prepare("
                INSERT INTO video_files (
                    upload_id, filename, original_filename, file_size, 
                    mime_type, file_data, checksum
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $uploadId,
                $filename,
                $upload['filename'],
                $totalSize,
                $mimeType,
                $completeFileData,
                $checksum
            ]);
            
            // Clean up chunks from temporary table
            $stmt = $this->dbConnection->prepare("DELETE FROM video_chunks WHERE upload_id = ?");
            $stmt->execute([$uploadId]);
            
            $totalTime = microtime(true) - $upload['startTime'];
            $avgSpeed = $upload['filesize'] / $totalTime;
            
            echo "[v0] Video stored successfully in database: $filename\n";
            echo "[v0] Total time: " . round($totalTime, 2) . "s, Average speed: " . $this->formatBytes($avgSpeed) . "/s\n";
            echo "[v0] Database storage size: " . $this->formatBytes($totalSize) . "\n";
            
            $conn->send(json_encode([
                'type' => 'upload_complete_ready_to_save',
                'uploadId' => $uploadId,
                'message' => '¡Video almacenado en base de datos! Guardando contenido automáticamente...',
                'filepath' => 'database:' . $uploadId, // Special identifier for database storage
                'filename' => $filename,
                'filesize' => $totalSize,
                'uploadTime' => round($totalTime, 2),
                'averageSpeed' => round($avgSpeed / 1024, 2),
                'autoSave' => true,
                'success' => true,
                'storageType' => 'database',
                'checksum' => $checksum
            ]));
            
            // Clean up memory
            unset($completeFileData);
            unset($chunks);
            
        } catch (Exception $e) {
            echo "[v0] Error assembling file: {$e->getMessage()}\n";
            $conn->send(json_encode([
                'type' => 'upload_error',
                'message' => 'Error ensamblando archivo: ' . $e->getMessage()
            ]));
            return;
        }
        
        $this->cleanupUpload($uploadId);
        $this->processUploadQueue();
    }

    private function getMimeType($extension) {
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'm4v' => 'video/x-m4v'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'video/mp4';
    }

    private function cleanupUpload($uploadId) {
        if (isset($this->uploads[$uploadId])) {
            try {
                $stmt = $this->dbConnection->prepare("DELETE FROM video_chunks WHERE upload_id = ?");
                $stmt->execute([$uploadId]);
            } catch (Exception $e) {
                echo "[v0] Warning: Could not clean up chunks for $uploadId: {$e->getMessage()}\n";
            }
            
            unset($this->uploads[$uploadId]);
            $this->activeUploads--;
            
            echo "[v0] Upload cleanup completed: $uploadId\n";
        }
    }

    private function cleanupStaleUploads() {
        $staleTime = 1800; // 30 minutes
        $currentTime = time();
        $cleaned = 0;
        
        foreach ($this->uploads as $uploadId => $upload) {
            if (($currentTime - $upload['lastActivity']) > $staleTime) {
                echo "[v0] Cleaning stale upload: $uploadId\n";
                $this->cleanupUpload($uploadId);
                $cleaned++;
            }
        }
        
        try {
            $stmt = $this->dbConnection->prepare("
                DELETE FROM video_chunks 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$staleTime]);
            $deletedChunks = $stmt->rowCount();
            
            if ($deletedChunks > 0) {
                echo "[v0] Cleaned $deletedChunks stale chunks from database\n";
            }
        } catch (Exception $e) {
            echo "[v0] Warning: Could not clean stale chunks: {$e->getMessage()}\n";
        }
        
        if ($cleaned > 0) {
            echo "[v0] Cleaned $cleaned stale uploads\n";
        }
        
        // Clean rate limiter
        foreach ($this->rateLimiter as $ip => $data) {
            if (($currentTime - $data['lastRequest']) > 3600) { // 1 hour
                unset($this->rateLimiter[$ip]);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        foreach ($this->uploads as $uploadId => $upload) {
            if ($upload['conn'] === $conn) {
                echo "[v0] Cleaning up upload due to connection close: $uploadId\n";
                $this->cleanupUpload($uploadId);
            }
        }
        
        echo "[v0] Conexión WebSocket cerrada: ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[v0] Error WebSocket: {$e->getMessage()}\n";
        
        foreach ($this->uploads as $uploadId => $upload) {
            if ($upload['conn'] === $conn) {
                $this->cleanupUpload($uploadId);
            }
        }
        
        $conn->close();
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function getClientIp($conn) {
        return $conn->remoteAddress ?? 'unknown';
    }
}

function checkPort($port) {
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

$port = 8080;

if (checkPort($port)) {
    echo "[v0] Puerto $port ya está en uso. Intentando liberar...\n";
    if (PHP_OS_FAMILY === 'Windows') {
        exec("taskkill /F /IM php.exe 2>nul", $output, $returnCode);
    } else {
        exec("pkill -f upload-server.php", $output, $returnCode);
    }
    sleep(3);
    
    if (checkPort($port)) {
        echo "[v0] Warning: Puerto $port aún en uso, intentando continuar...\n";
    }
}

try {
    echo "[v0] Creando servidor WebSocket de producción...\n";
    
    $uploadServer = new ProductionVideoUploadServer();
    $server = IoServer::factory(
        new HttpServer(
            new WsServer($uploadServer)
        ),
        $port
    );

    echo "=== StreamFlix Production WebSocket Server ===\n";
    echo "Servidor WebSocket iniciado exitosamente en puerto $port\n";
    echo "Servidor optimizado para producción con gestión de tráfico\n";
    echo "Memoria disponible: " . ini_get('memory_limit') . "\n";
    echo "Uploads concurrentes máximos: 10\n";
    echo "Tamaño máximo de archivo: 2GB\n";
    echo "Video storage: Database (LONGBLOB)\n";
    echo "Presiona Ctrl+C para detener el servidor\n";
    echo "=============================================\n";
    
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, function() {
            echo "\n[v0] Cerrando servidor WebSocket...\n";
            exit(0);
        });
        pcntl_signal(SIGINT, function() {
            echo "\n[v0] Cerrando servidor WebSocket...\n";
            exit(0);
        });
    }
    
    $server->run();
    
} catch (Exception $e) {
    echo "[v0] Error crítico iniciando servidor WebSocket: {$e->getMessage()}\n";
    echo "[v0] Stack trace: {$e->getTraceAsString()}\n";
    exit(1);
}
?>
