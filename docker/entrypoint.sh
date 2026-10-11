#!/bin/sh

cd /var/www/html

# Enlace simbólico para archivos públicos (imágenes de productos, etc.)
php artisan storage:link >/dev/null 2>&1 || true

# Esperar a la base de datos y ejecutar migraciones
intento=0
until php artisan migrate --force; do
    intento=$((intento + 1))
    if [ "$intento" -ge 30 ]; then
        echo "[entrypoint] No se pudo migrar tras $intento intentos. Continuando de todos modos..."
        break
    fi
    echo "[entrypoint] Esperando base de datos... intento $intento"
    sleep 2
done

# Limpiar cachés para tomar configuración/plantillas actualizadas
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

exec apache2-foreground
