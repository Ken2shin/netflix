#!/bin/bash

echo "=== StreamFlix Startup Script ==="
echo "Iniciando servidor completo..."

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "Instalando dependencias de Composer..."
    composer install
fi

# Start WebSocket server in background
echo "Iniciando servidor WebSocket..."
php websocket/upload-server.php &
WEBSOCKET_PID=$!

echo "Servidor WebSocket iniciado (PID: $WEBSOCKET_PID)"
echo "Servidor principal disponible en: http://localhost:3000"
echo ""
echo "Para detener todos los servicios:"
echo "kill $WEBSOCKET_PID"
echo ""
echo "Presiona Ctrl+C para detener este script"

# Keep script running
trap "kill $WEBSOCKET_PID; exit" INT TERM
wait
