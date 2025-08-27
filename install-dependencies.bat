@echo off
echo === Instalando dependencias de StreamFlix ===
echo.

REM Check if composer is installed
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Composer no está instalado.
    echo Descarga e instala Composer desde: https://getcomposer.org/
    pause
    exit /b 1
)

echo Instalando dependencias PHP...
composer install --no-dev --optimize-autoloader

if %errorlevel% neq 0 (
    echo Error instalando dependencias.
    pause
    exit /b 1
)

echo.
echo === Dependencias instaladas correctamente ===
echo Ahora puedes ejecutar: php start-server.php
pause
