@echo off
REM Create Windows batch script to start everything together

echo === StreamFlix Startup Script ===
echo Iniciando servidor completo...

REM Install dependencies if needed
if not exist "vendor" (
    echo Instalando dependencias de Composer...
    composer install
)

REM Start WebSocket server in background
echo Iniciando servidor WebSocket...
start /B php websocket/upload-server.php

echo Servidor WebSocket iniciado
echo Servidor principal disponible en: http://localhost:3000
echo.
echo Para detener el servidor WebSocket:
echo taskkill /F /IM php.exe
echo.
pause
