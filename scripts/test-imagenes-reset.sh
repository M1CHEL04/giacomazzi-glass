#!/bin/sh
# Deja el fileserver de TEST en cero: borra todas las imágenes de producto del
# volumen giaco-test_fileserver_test_data. Se corre desde ~/giacomazzi-glass-test.
#
# Uso:
#   ./scripts/test-imagenes-reset.sh            # solo archivos, pide confirmación
#   ./scripts/test-imagenes-reset.sh --con-db   # además vacía `imagenes_producto`
#   ./scripts/test-imagenes-reset.sh -y         # sin confirmación (para automatizar)
#
# Ojo: borrar los archivos sin limpiar la tabla deja las filas apuntando a URLs
# muertas y el catálogo de test muestra imágenes rotas. Por eso está --con-db.
#
# NUNCA apunta a prod: el compose y los contenedores son los de test, fijos.
set -e

cd "$(dirname "$0")/.."

COMPOSE="compose.test.yml"
UPLOAD="/home/giacomazzi/upload"
CON_DB=0
SIN_PREGUNTAR=0

for arg in "$@"; do
    case "$arg" in
        --con-db) CON_DB=1 ;;
        -y|--yes) SIN_PREGUNTAR=1 ;;
        *) echo "✗ Opción desconocida: $arg"; exit 1 ;;
    esac
done

# fileserver-test normalmente queda arriba (test-down.sh no lo baja), pero si
# alguien lo frenó lo levantamos: sin el contenedor no hay dónde ejecutar el rm.
if [ -z "$(docker compose -f "$COMPOSE" ps -q fileserver-test)" ]; then
    echo "▶ fileserver-test no está corriendo, levantándolo..."
    docker compose -f "$COMPOSE" up -d fileserver-test
fi

echo "▶ Contenido actual del fileserver de test:"
docker compose -f "$COMPOSE" exec -T fileserver-test sh -c \
    "find $UPLOAD -type f | wc -l | xargs echo '  archivos:'; du -sh $UPLOAD | cut -f1 | xargs echo '  tamaño:  '"

if [ "$SIN_PREGUNTAR" -eq 0 ]; then
    if [ "$CON_DB" -eq 1 ]; then
        echo "Se borran TODOS los archivos y se vacía la tabla imagenes_producto de TEST."
    else
        echo "Se borran TODOS los archivos (la tabla imagenes_producto queda intacta)."
    fi
    printf "¿Continuar? [escribí 'si'] "
    read -r respuesta
    [ "$respuesta" = "si" ] || { echo "✗ Cancelado."; exit 1; }
fi

# Se borra el contenido de upload, no el directorio: atmoz/sftp hace chroot ahí
# y sin ese dir (con su ownership) se rompe el login SFTP.
echo "▶ Borrando archivos..."
docker compose -f "$COMPOSE" exec -T fileserver-test sh -c \
    "rm -rf $UPLOAD/..?* $UPLOAD/.[!.]* $UPLOAD/* 2>/dev/null || true"

if [ "$CON_DB" -eq 1 ]; then
    if [ -z "$(docker compose -f "$COMPOSE" ps -q mysql-test)" ]; then
        echo "▶ mysql-test no está corriendo, levantándolo..."
        docker compose -f "$COMPOSE" up -d mysql-test
    fi
    # Las credenciales salen del env del propio contenedor (env_file: .env.test),
    # así no quedan en el argv del host ni en el historial del shell.
    # DELETE y no TRUNCATE por si alguna FK apunta a la tabla.
    echo "▶ Vaciando imagenes_producto..."
    docker compose -f "$COMPOSE" exec -T mysql-test sh -c \
        'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "DELETE FROM imagenes_producto;"'
fi

echo "▶ Verificando..."
docker compose -f "$COMPOSE" exec -T fileserver-test sh -c \
    "find $UPLOAD -type f | wc -l | xargs echo '  archivos restantes:'; ls -la $UPLOAD"

echo "✔ Fileserver de test en cero."
if [ "$CON_DB" -eq 0 ]; then
    echo "  Nota: la tabla imagenes_producto no se tocó — el catálogo de test va a"
    echo "  mostrar imágenes rotas. Corré con --con-db si querés limpiarla también."
fi
