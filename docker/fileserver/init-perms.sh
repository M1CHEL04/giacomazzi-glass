#!/bin/sh
# Ownership del directorio de uploads para que el usuario SFTP pueda escribir
chown 1001:users /home/giacomazzi/upload
chmod 755 /home/giacomazzi/upload

# Umask 0022 en sftp-server → archivos subidos quedan 644 (legibles por nginx)
# Idempotente: solo modifica si todavía no está configurado
if ! grep -q "\-u 0022" /etc/ssh/sshd_config; then
    sed -i 's|^\(Subsystem.*sftp.*\)|\1 -u 0022|' /etc/ssh/sshd_config
fi
