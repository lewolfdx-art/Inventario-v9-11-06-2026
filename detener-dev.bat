@echo off
title Detener Sistema de Inventario - DESARROLLO
cd /d D:\Inventario-v9
echo Deteniendo el entorno de desarrollo...
docker compose down
echo.
echo Sistema DEV detenido.
pause
