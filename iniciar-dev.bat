@echo off
title Sistema de Inventario - DESARROLLO
color 0B
cd /d D:\Inventario-v9

echo ========================================
echo    SISTEMA DE INVENTARIO  [DEV]
echo ========================================
echo.

echo Verificando Docker...
docker version >nul 2>&1
if errorlevel 1 (
    echo Docker no esta corriendo. Iniciandolo...
    start "" /min "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    :esperar_docker
    timeout /t 5 >nul
    docker version >nul 2>&1
    if errorlevel 1 (
        echo Docker aun no esta listo, esperando...
        goto esperar_docker
    )
)
echo Docker esta listo.
echo.

echo Deteniendo el entorno cliente si estuviera activo (comparten la misma BD)...
if exist "D:\SistemaInventario\docker-compose.yml" (
    pushd D:\SistemaInventario
    docker compose down >nul 2>&1
    popd
)

echo.
echo Preparando base de datos compartida...
docker volume create inventario_dbdata >nul 2>&1

echo.
echo Construyendo e iniciando DEV (la primera vez tarda unos minutos)...
docker compose up -d --build

echo.
echo Esperando servicios...
timeout /t 5 >nul

echo.
echo ========================================
echo    Listo! Abriendo en http://localhost:8080
echo ========================================
start http://localhost:8080

echo.
echo Edita tu codigo y recarga el navegador: los cambios se ven al instante.
echo Para APAGAR, usa detener-dev.bat
timeout /t 3 >nul
exit
