#!/bin/bash

# Comprobar argumentos
if [ "$#" -ne 2 ]; then
    echo "Uso: $0 /ruta/proyecto/local /ruta/destino/servidor"
    exit 1
fi

SOURCE=$(realpath "$1")
DESTINY=$(realpath "$2")
USER_ACTUAL=$(whoami)

echo "🚀 Iniciando despliegue de Laravel..."
echo "De: $SOURCE"
echo "A:  $DESTINY"

# 1. Función para ejecutar comandos con o sin sudo según disponibilidad de escritura
run_cmd() {
    if [ -w "$DESTINY" ] || [ ! -d "$DESTINY" ]; then
        eval "$@"
    else
        eval "sudo $@"
    fi
}

# 2. Crear directorio destino si no existe
run_cmd "mkdir -p $DESTINY"

# 3. Sincronizar archivos con rsync
# Excluimos: .env (prohibido pisar), git, node_modules y storage (para no borrar fotos de usuarios)
echo "📦 Sincronizando archivos..."
run_cmd "rsync -avz --progress --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='.env' \
    --exclude='/storage/*.key' \
    --exclude='/storage/app/public/*' \
    --exclude='/storage/framework/cache/data/*' \
    --exclude='/storage/framework/sessions/*' \
    --exclude='/storage/framework/views/*' \
    --exclude='/storage/logs/*' \
    --exclude='/public/storage' \
    \"$SOURCE/\" \"$DESTINY/\""

# 4. Ajustar permisos de directorios críticos
echo "🔐 Ajustando permisos (storage y bootstrap/cache)..."
run_cmd "chown -R $USER_ACTUAL:www-data $DESTINY"
run_cmd "find $DESTINY -type d -exec chmod 755 {} \;"
run_cmd "find $DESTINY -type f -exec chmod 644 {} \;"

# Permisos de escritura para el servidor web (Debian usa www-data)
run_cmd "chmod -R 775 $DESTINY/storage"
run_cmd "chmod -R 775 $DESTINY/bootstrap/cache"

# 5. Comandos Post-Despliegue (Caché de Laravel)
echo "⚡ Optimizando Laravel..."
if [ -f "$DESTINY/artisan" ]; then
    run_cmd "php $DESTINY/artisan migrate --force"
    run_cmd "php $DESTINY/artisan config:cache"
    run_cmd "php $DESTINY/artisan route:cache"
    run_cmd "php $DESTINY/artisan view:cache"
    # Crear link simbólico si no existe
    if [ ! -L "$DESTINY/public/storage" ]; then
        run_cmd "php $DESTINY/artisan storage:link"
    fi
fi

echo "✅ Despliegue completado con éxito."
