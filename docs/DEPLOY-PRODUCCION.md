# Despliegue — Giacomazzi Glass

Deploys por rama en un único VPS, sin Jenkins:
- **prod** ⇐ rama `main` (`~/giacomazzi-glass`) — siempre arriba, junto con el reverse proxy.
- **test** ⇐ rama `test` (`~/giacomazzi-glass-test`, git worktree) — efímero, on-demand.

Dos archivos compose:
- [compose.prod.yml](../compose.prod.yml): `nginx-proxy`, `certbot`, `app-prod`, `mysql-prod`, `fileserver-prod`.
- [compose.test.yml](../compose.test.yml): `app-test`, `mysql-test`, `fileserver-test`.

Cada `app-*` sirve sus propios `/files/` desde su fileserver (el proxy es solo TLS + ruteo). Único recurso
compartido entre ambos compose: la red externa `proxy_net`.

> **Estado actual:** el cert de `giacomazzi-test.duckdns.org` ya existe y funciona en HTTPS. La migración de
> abajo **reusa ese certificado** (no se re-emite, no se pasa por HTTP). Prod se despliega más adelante.

---

## 0. Migración desde el compose único (una sola vez, en el server)

> Requisito: la reestructuración (compose.prod.yml, compose.test.yml, scripts, cambios de nginx) ya tiene
> que estar **commiteada y pusheada a `main` y a `test`** (mergear `main` → `test`).

### 0.1 Bajar el stack viejo conservando los datos
Con el `docker-compose.yml` viejo **todavía presente** (antes de hacer pull):
```bash
cd ~/giacomazzi-glass
docker compose down          # SIN -v → conserva los volúmenes (certs, datos, imágenes)
```
Esto libera los nombres de contenedor y los puertos 80/443.

### 0.2 Traer el código reestructurado
```bash
git pull origin main         # elimina docker-compose.yml, agrega compose.prod/test.yml y scripts/
```

### 0.3 Crear la red compartida
```bash
docker network create proxy_net
```

### 0.4 Preservar los volúmenes existentes (copia old → nuevos nombres)
Los volúmenes viejos tienen prefijo `giacomazzi-glass_*`. Los nuevos compose usan los prefijos de proyecto
`giaco-prod_*` y `giaco-test_*`. Copiamos lo que hay que conservar **antes** del primer `up`:

```bash
# Certificado SSL de test (imprescindible para arrancar en HTTPS sin bootstrap por HTTP)
docker volume create giaco-prod_certbot_conf
docker run --rm -v giacomazzi-glass_certbot_conf:/from:ro -v giaco-prod_certbot_conf:/to \
  alpine sh -c "cp -a /from/. /to/"

docker volume create giaco-prod_certbot_www
docker run --rm -v giacomazzi-glass_certbot_www:/from:ro -v giaco-prod_certbot_www:/to \
  alpine sh -c "cp -a /from/. /to/"

# Imágenes de producto de test (compartidas con el dev local) — conservar
docker volume create giaco-test_fileserver_test_data
docker run --rm -v giacomazzi-glass_fileserver_test_data:/from:ro -v giaco-test_fileserver_test_data:/to \
  alpine sh -c "cp -a /from/. /to/"

# Base de datos de test — conservar para no re-sembrar
docker volume create giaco-test_mysql_test_data
docker run --rm -v giacomazzi-glass_mysql_test_data:/from:ro -v giaco-test_mysql_test_data:/to \
  alpine sh -c "cp -a /from/. /to/"
```
> Los volúmenes viejos quedan intactos como respaldo; se pueden borrar una vez confirmado que todo anda.

### 0.5 Crear el worktree de test
```bash
cd ~/giacomazzi-glass
git worktree add ../giacomazzi-glass-test test
```

### 0.6 Archivos de entorno (gitignored, en cada carpeta)
- `~/giacomazzi-glass/.env` con `FILESERVER_PROD_SFTP_PASSWORD=...` + `~/giacomazzi-glass/.env.prod`
- `~/giacomazzi-glass-test/.env` con `FILESERVER_TEST_SFTP_PASSWORD=...` + `~/giacomazzi-glass-test/.env.test`

---

## 1. Deploy de TEST (HTTPS, reusando el certificado)

### 1.1 Levantar el proxy (una vez; queda siempre arriba)
El proxy vive en `compose.prod.yml`, pero se levanta **solo el servicio del proxy** (no toca app-prod):
```bash
cd ~/giacomazzi-glass
docker compose -f compose.prod.yml up -d nginx-proxy
docker exec giacomazzi-proxy nginx -t     # debe decir OK
docker compose -f compose.prod.yml ps nginx-proxy   # Up
```
El proxy arranca directo en HTTPS porque el volumen `giaco-prod_certbot_conf` ya tiene el cert de test
(paso 0.4). Los bloques de prod en [nginx-https.conf](../docker/proxy/nginx-https.conf) siguen comentados
hasta que exista el dominio/cert de prod.

### 1.2 Levantar el stack de test
```bash
cd ~/giacomazzi-glass-test
git pull origin test
docker compose -f compose.test.yml up -d --build
docker compose -f compose.test.yml exec -T app-test php artisan migrate --force
```
Esto está automatizado en `./scripts/deploy-test.sh` (hace exactamente lo de arriba).

### 1.3 Verificar
- `https://giacomazzi-test.duckdns.org` responde por HTTPS (candado válido, Let's Encrypt).
- Una imagen de producto se sirve desde `https://giacomazzi-test.duckdns.org/files/...`
  (ahora la sirve `app-test`, no el proxy).
- `docker compose -f compose.test.yml ps` → todo `Up`.

---

## 2. Prender / apagar test (efímero)

- **Bajar** (deja `fileserver-test` arriba para que el dev local siga con sus imágenes vía SFTP 2222):
  ```bash
  cd ~/giacomazzi-glass-test && ./scripts/test-down.sh
  ```
- **Volver a subir** lo ya construido (sin rebuild): `./scripts/test-up.sh`
- **Redeploy con cambios nuevos** de la rama test: `./scripts/deploy-test.sh`

Con test abajo, `https://giacomazzi-test.duckdns.org` devuelve 502 (esperado). Prod y el proxy no se afectan.

---

## 3. Deploy de PROD (en unas semanas)

Cuando tengas el **dominio de producción** apuntando al server:

1. En [.env.prod](../.env.prod): `APP_KEY`, passwords, `APP_URL=https://tu-dominio.com`,
   `SFTP_URL=https://tu-dominio.com/files`, `APP_DEBUG=false`.
2. Emitir el cert de prod (el proxy ya sirve `/.well-known/acme-challenge/` por el puerto 80; **no baja
   HTTPS de test**):
   ```bash
   cd ~/giacomazzi-glass
   docker compose -f compose.prod.yml run --rm certbot certonly --webroot \
     --webroot-path /var/www/certbot --email santymichel016@gmail.com --agree-tos --no-eff-email \
     -d tu-dominio.com -d www.tu-dominio.com
   ```
3. **Descomentar** los bloques de PROD en [nginx-https.conf](../docker/proxy/nginx-https.conf) y reemplazar
   `tu-dominio.com` (server_name + rutas de cert). Agregarles la línea de HSTS como en el bloque de test.
4. Levantar prod y recargar el proxy:
   ```bash
   docker compose -f compose.prod.yml up -d --build
   docker exec giacomazzi-proxy nginx -s reload
   ```
5. Deploys posteriores de prod: `./scripts/deploy-prod.sh` (no reinicia el proxy).

> **Migraciones de prod:** se corren **a mano, por fuera del deploy**, cuando corresponda y con backup previo.
> El `deploy-prod.sh` a propósito **no** migra.

---

## 4. Renovación automática del certificado

Let's Encrypt vence a los 90 días. Cron en el host (el proxy siempre arriba sirve la validación, así que el
cert de test renueva aunque `app-test` esté abajo):
```cron
0 3 * * * cd /root/giacomazzi-glass && docker compose -f compose.prod.yml run --rm certbot renew --quiet && docker exec giacomazzi-proxy nginx -s reload
```

---

## Comandos útiles

```bash
# Estado
docker compose -f compose.prod.yml ps
docker compose -f compose.test.yml ps

# Logs
docker compose -f compose.prod.yml logs nginx-proxy --tail=30
docker compose -f compose.test.yml logs app-test --tail=50

# Rebuild solo app-prod (no toca el proxy)
cd ~/giacomazzi-glass && docker compose -f compose.prod.yml up -d --build app-prod

# Recrear app-test para tomar cambios de .env.test (sin rebuild)
cd ~/giacomazzi-glass-test && docker compose -f compose.test.yml up -d --force-recreate app-test
```
