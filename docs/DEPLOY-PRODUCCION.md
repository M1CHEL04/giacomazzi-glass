# Despliegue a Producción — Giacomazzi Glass

Guía paso a paso para levantar el entorno de **producción** replicando toda la
configuración que ya está funcionando en test (base de datos, file server SFTP,
reverse proxy nginx, certificados Let's Encrypt y URLs HTTPS).

> **Antes de empezar:** todos los comandos se ejecutan en el servidor, dentro del
> directorio del proyecto (`~/giacomazzi-glass` en el server actual).

---

## 0. Prerequisitos

- [ ] **Dominio de producción** comprado/configurado, apuntando (registro `A`) a la
      IP pública del servidor. Reemplazá `tu-dominio.com` por el dominio real en
      todos los pasos de abajo.
- [ ] Puertos **80** y **443** abiertos en el firewall del servidor.
- [ ] El código actualizado en el server (`git pull origin main`). Esto ya incluye
      el fix de `trustProxies` en [bootstrap/app.php](../bootstrap/app.php), que hace
      que Laravel genere URLs `https://` detrás del proxy — **aplica a prod sin tocar
      nada más**.

---

## 1. Configurar `.env.prod` con secretos reales

El archivo [.env.prod](../.env.prod) viene con placeholders. Antes de desplegar hay
que reemplazar **obligatoriamente**:

| Variable | Acción |
|----------|--------|
| `APP_KEY` | Generar con `php artisan key:generate --show` y pegar el valor |
| `APP_URL` | `https://tu-dominio.com` |
| `APP_DEBUG` | Debe quedar en `false` |
| `DB_PASSWORD` / `MYSQL_PASSWORD` | Misma contraseña fuerte (deben coincidir) |
| `MYSQL_ROOT_PASSWORD` | Contraseña fuerte distinta a la anterior |
| `SFTP_PASSWORD` | Coincidir con `FILESERVER_PROD_SFTP_PASSWORD` (ver paso 2) |
| `SFTP_URL` | `https://tu-dominio.com/files` |

> ⚠️ `APP_URL` y `SFTP_URL` **deben ir con `https://`**, igual que hicimos en test.
> Si quedan en `http://` el formulario vuelve a marcar "conexión no segura".

### Variable del host para el SFTP

El `docker-compose.yml` lee `${FILESERVER_PROD_SFTP_PASSWORD}` desde el entorno del
host (no desde `.env.prod`). Definila en un archivo `.env` en la raíz (el que lee
docker compose por defecto) o exportala:

```bash
# .env (raíz del proyecto, junto al docker-compose.yml)
FILESERVER_PROD_SFTP_PASSWORD=f1l3S3rv3RG14c0PrD   # = SFTP_PASSWORD de .env.prod
```

---

## 2. Levantar los contenedores en HTTP (config inicial)

El [docker-compose.yml](../docker-compose.yml) arranca con `nginx.conf` (HTTP puro).
Esto es **intencional**: necesitamos nginx en HTTP para que certbot pueda validar el
dominio antes de tener el certificado.

```bash
docker compose up -d --build app-prod mysql-prod fileserver-prod nginx-proxy
```

Verificá que todo esté `Up` (no `Restarting`):

```bash
docker compose ps
```

Deberías ver `Up`: `giacomazzi-proxy`, `giacomazzi-app-prod`,
`giacomazzi-mysql-prod`, `giacomazzi-fileserver-prod`.

> El file server aplica permisos solo con [init-perms.sh](../docker/fileserver/init-perms.sh)
> al arrancar (umask 0022 → archivos 644 legibles por nginx). No hay que hacer nada manual.

---

## 3. Migrar y poblar la base de datos

```bash
docker compose exec app-prod php artisan migrate --force
docker compose exec app-prod php artisan db:seed --force
```

`--force` es obligatorio en producción (Laravel pide confirmación si no).
El `db:seed` crea usuarios ([UserSeeder](../database/seeders/UserSeeder.php)) y
categorías ([CategoriasSeeder](../database/seeders/CategoriasSeeder.php)).

---

## 4. Obtener el certificado Let's Encrypt

Con nginx corriendo en HTTP, pedí el certificado para el dominio de prod. **Comando
en una sola línea** (incluí `www` si el dominio lo usa):

```bash
docker compose run --rm certbot certonly --webroot --webroot-path /var/www/certbot --email santymichel016@gmail.com --agree-tos --no-eff-email -d tu-dominio.com -d www.tu-dominio.com
```

Tiene que terminar con:

```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/tu-dominio.com/fullchain.pem
```

> El contenedor `certbot` termina con `Exited` — **es normal**, no es un servicio
> persistente. Lo único que importa es el mensaje de éxito. Verificá con:
> ```bash
> docker compose run --rm certbot certificates
> ```

---

## 5. Activar los bloques de producción en `nginx-https.conf`

En [docker/proxy/nginx-https.conf](../docker/proxy/nginx-https.conf), los bloques de
prod están **comentados** (los dejamos así para que test funcionara solo). Ahora:

1. **Descomentá** los dos `server {}` de PROD (HTTP→HTTPS y HTTPS).
2. **Reemplazá** `tu-dominio.com` y `www.tu-dominio.com` por el dominio real en:
   - `server_name` (ambos bloques)
   - `ssl_certificate` → `/etc/letsencrypt/live/tu-dominio.com/fullchain.pem`
   - `ssl_certificate_key` → `/etc/letsencrypt/live/tu-dominio.com/privkey.pem`

> La ruta de `live/` debe coincidir **exactamente** con el primer `-d` del comando de
> certbot. Si pediste `-d tu-dominio.com`, la carpeta es `live/tu-dominio.com/`.

---

## 6. Cambiar el proxy a la config HTTPS

En [docker-compose.yml](../docker-compose.yml), en el servicio `nginx-proxy`, cambiá
el montaje de la config:

```yaml
# antes
- ./docker/proxy/nginx.conf:/etc/nginx/nginx.conf:ro
# después
- ./docker/proxy/nginx-https.conf:/etc/nginx/nginx.conf:ro
```

Recreá solo el proxy:

```bash
docker compose up -d --force-recreate nginx-proxy
docker compose ps nginx-proxy   # debe quedar Up, no Restarting
```

> Si queda en `Restarting`, casi siempre es que la ruta del certificado en
> `nginx-https.conf` no coincide con la carpeta real en `live/`. Revisá con
> `docker logs giacomazzi-proxy --tail=20`.

---

## 7. Configurar la renovación automática del certificado

Let's Encrypt vence a los 90 días. Agregá un cron en el **host** (no en un
contenedor):

```bash
crontab -e
```

```cron
# Renueva los certs y recarga nginx si hubo cambios — 3:00 AM todos los días
0 3 * * * cd /root/giacomazzi-glass && docker compose run --rm certbot renew --quiet && docker compose exec -T nginx-proxy nginx -s reload
```

Ajustá la ruta `/root/giacomazzi-glass` si el proyecto está en otro lado.

---

## 8. Verificación final

- [ ] `docker compose ps` → todos los servicios de prod en `Up`.
- [ ] Desde el server: `curl -I https://tu-dominio.com` responde `200` y
      `SSL certificate verify ok`.
- [ ] En el browser: el candado muestra el cert emitido por **Let's Encrypt** con el
      dominio correcto.
- [ ] Inspeccionar el `<form>` de login → el atributo `action` empieza con `https://`
      (gracias al fix de `trustProxies`).
- [ ] Subir una imagen de producto y confirmar que se sirve desde
      `https://tu-dominio.com/files/...`.

---

## Comandos útiles de mantenimiento (prod)

```bash
# Reconstruir SOLO la app tras un cambio de código (rebuild de imagen)
docker compose up -d --build app-prod

# Recrear SOLO la app para tomar cambios de .env.prod (sin rebuild)
docker compose up -d --force-recreate app-prod

# Logs
docker compose logs app-prod   --tail=50
docker compose logs nginx-proxy --tail=30
docker compose logs mysql-prod  --tail=30

# Limpiar cachés de Laravel si algo se ve desactualizado
docker compose exec app-prod php artisan config:clear
docker compose exec app-prod php artisan cache:clear
docker compose exec app-prod php artisan view:clear
```

---

## Resumen del flujo (por qué este orden)

Es un problema de huevo y gallina:

1. **nginx en HTTP** → único modo de arrancar sin tener todavía el certificado.
2. **certbot valida** vía `/.well-known/acme-challenge/` y guarda el cert en el
   volumen `certbot_conf`.
3. **nginx cambia a HTTPS** → ahora sí encuentra los `.pem` y levanta con SSL.
4. **cron renueva** cada 90 días y recarga nginx.

Saltarse el orden (arrancar directo en HTTPS sin cert) hace que nginx crashee en
bucle (`Restarting`), que fue exactamente el error que tuvimos en test.
