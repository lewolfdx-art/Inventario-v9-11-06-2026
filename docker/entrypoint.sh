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

# Optimizar cachés (config + rutas + eventos) para rendimiento.
# Solo se cachea cuando RUN_OPTIMIZE=1 (entorno cliente/produccion).
# En desarrollo (RUN_OPTIMIZE ausente) se limpian las cachés para que
# los cambios de codigo montados en vivo se reflejen al instante.
if [ "$RUN_OPTIMIZE" = "1" ]; then
    php artisan config:cache >/dev/null 2>&1 || true
    php artisan route:cache >/dev/null 2>&1 || true
    php artisan event:cache >/dev/null 2>&1 || true
else
    php artisan config:clear >/dev/null 2>&1 || true
    php artisan route:clear >/dev/null 2>&1 || true
    php artisan event:clear >/dev/null 2>&1 || true
    php artisan view:clear >/dev/null 2>&1 || true
fi

exec apache2-foreground
