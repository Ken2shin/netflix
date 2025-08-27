<?php
echo "=== StreamFlix Server Startup ===\n";
echo "Verificando dependencias...\n";

if (!file_exists('vendor/autoload.php')) {
    echo "Instalando dependencias de Composer...\n";
    $composerCommands = [
        'composer install --no-dev --optimize-autoloader',
        'composer require ratchet/pawl ratchet/socket-io ratchet/rfc6455'
    ];
    
    foreach ($composerCommands as $command) {
        echo "Ejecutando: $command\n";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "Error ejecutando: $command\n";
            echo "Output: " . implode("\n", $output) . "\n";
        }
    }
    
    if (!file_exists('vendor/autoload.php')) {
        echo "Error: No se pudo instalar las dependencias.\n";
        echo "Ejecuta manualmente: composer install\n";
        exit(1);
    }
    echo "Dependencias instaladas correctamente.\n";
}

require_once 'vendor/autoload.php';

function checkWebSocketServer($port = 8080, $timeout = 5) {
    $startTime = time();
    while ((time() - $startTime) < $timeout) {
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        usleep(500000); // Wait 0.5 seconds
    }
    return false;
}

function killExistingWebSocketProcesses() {
    echo "Verificando procesos WebSocket existentes...\n";
    
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows - kill php processes running upload-server.php
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul', $output);
        foreach ($output as $line) {
            if (strpos($line, 'upload-server.php') !== false) {
                exec('taskkill /F /IM php.exe /FI "WINDOWTITLE eq upload-server.php*" 2>nul');
                echo "Proceso WebSocket anterior terminado.\n";
                break;
            }
        }
    } else {
        // Linux/Mac
        exec('pkill -f upload-server.php 2>/dev/null');
        echo "Procesos WebSocket anteriores terminados.\n";
    }
    
    sleep(2); // Wait for processes to terminate
}

echo "Iniciando servidor WebSocket...\n";

// Check if Ratchet is available
if (!class_exists('Ratchet\Server\IoServer')) {
    echo "Error: Ratchet WebSocket library no encontrada.\n";
    echo "Instalando Ratchet...\n";
    
    $ratchetInstall = 'composer require ratchet/pawl ratchet/socket-io ratchet/rfc6455';
    exec($ratchetInstall, $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "Error instalando Ratchet. Ejecuta manualmente:\n";
        echo "composer require ratchet/pawl ratchet/socket-io ratchet/rfc6455\n";
        exit(1);
    }
    
    // Reload autoloader
    require_once 'vendor/autoload.php';
    
    if (!class_exists('Ratchet\Server\IoServer')) {
        echo "Error: No se pudo cargar Ratchet después de la instalación.\n";
        exit(1);
    }
}

killExistingWebSocketProcesses();

$websocketCommand = 'php websocket/upload-server.php';
$port = 8080;

if (PHP_OS_FAMILY === 'Windows') {
    // Windows - start in new window that stays open
    $command = "start \"StreamFlix WebSocket\" /MIN cmd /k \"$websocketCommand\"";
} else {
    // Linux/Mac - start in background with nohup
    $command = "nohup $websocketCommand > websocket.log 2>&1 &";
}

echo "Ejecutando: $command\n";
exec($command);

echo "Verificando que el servidor WebSocket esté respondiendo...\n";
$attempts = 0;
$maxAttempts = 10;

while ($attempts < $maxAttempts) {
    if (checkWebSocketServer($port)) {
        echo "✓ Servidor WebSocket verificado y funcionando en puerto $port\n";
        break;
    }
    
    $attempts++;
    echo "Intento $attempts/$maxAttempts - Esperando que el servidor WebSocket inicie...\n";
    sleep(1);
}

if ($attempts >= $maxAttempts) {
    echo "✗ Error: El servidor WebSocket no pudo iniciarse correctamente.\n";
    echo "Verifica el archivo websocket.log para más detalles.\n";
    echo "Comandos de diagnóstico:\n";
    echo "  - Verificar puerto: netstat -an | findstr :$port\n";
    echo "  - Ver log: type websocket.log (Windows) o cat websocket.log (Linux/Mac)\n";
    exit(1);
}

echo "Servidor principal listo\n";
echo "\n=== Información del Servidor ===\n";
echo "WebSocket Server: Activo en puerto $port\n";
echo "Upload Directory: uploads/videos/\n";
echo "Log File: websocket.log\n";

if (PHP_OS_FAMILY === 'Windows') {
    echo "\nPara detener el servidor:\n";
    echo "  - Cierra la ventana del WebSocket Server\n";
    echo "  - O ejecuta: taskkill /F /IM php.exe\n";
} else {
    echo "\nPara detener el servidor:\n";
    echo "  - Ejecuta: pkill -f upload-server.php\n";
    echo "  - O usa: ps aux | grep upload-server.php\n";
}

echo "\n=== Servidor iniciado correctamente ===\n";
echo "El servidor WebSocket está listo para recibir uploads.\n";
echo "Puedes cerrar esta ventana, el WebSocket seguirá funcionando.\n";
?>
