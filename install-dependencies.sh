#!/bin/bash
echo "=== Instalando dependencias de StreamFlix ==="
echo

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer no está instalado."
    echo "Instala Composer desde: https://getcomposer.org/"
    exit 1
fi

echo "Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "Error instalando dependencias."
    exit 1
fi

echo
echo "=== Dependencias instaladas correctamente ==="
echo "Ahora puedes ejecutar: php start-server.php"
