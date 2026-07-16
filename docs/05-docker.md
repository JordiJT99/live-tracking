# Docker — el porqué de cada decisión de contenedores

Este documento explica **cómo está montado el `docker compose` y por qué cada Dockerfile es como es**. El objetivo de la prueba es que todo arranque con un único comando en la máquina del evaluador; este documento justifica cada pieza que lo hace posible.

```sh
docker compose up -d --build
```

---

## Visión general

Cinco servicios, una red bridge única (`live-tracking_default`), un solo comando:

```
                    docker compose up -d --build
                                 │
   ┌───────────┬─────────────────┼──────────────┬───────────┐
   ▼           ▼                 ▼              ▼           ▼
 mysql      backend           simulator      frontend    adminer
 :3307      :8000             :8001          :8090       :8091
 (BD)       (API Laravel)     (micro DDD)    (nginx+SPA) (inspector BD)
   ▲           │  ▲               │              │
   │           │  └── HTTP interno┘              │  proxy /api → backend
   └───────────┴─────── escritura / lectura SQL ─┘
```

- **`mysql`** — base de datos compartida. Único puerto que persiste datos (volumen `mysql-data`).
- **`backend`** — API REST Laravel. Corre migraciones + seeder al arrancar. Lee de la BD y delega en el micro.
- **`simulator`** — microservicio DDD. Único escritor de `services` y `tracking`.
- **`frontend`** — Vue compilado a estáticos, servido por nginx, que además hace de proxy hacia el backend.
- **`adminer`** — inspector web de la BD (conveniencia para el revisor; la app no lo necesita).

---

## `docker-compose.yml` — explicado servicio a servicio

### `mysql`

```yaml
mysql:
  image: mysql:8
  environment:
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root}
    MYSQL_DATABASE: ${DB_DATABASE:-live_tracking}
    MYSQL_USER: ${DB_USERNAME:-laravel}
    MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
  ports:
    - "3307:3306"
  volumes:
    - mysql-data:/var/lib/mysql
  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD:-root}"]
    interval: 5s
    timeout: 5s
    retries: 20
```

- **`${VAR:-default}`** — el compose trae valores por defecto que funcionan sin crear ningún `.env`. El evaluador no tiene que configurar nada; si quiere, puede sobreescribir con variables de entorno.
- **`volumes: mysql-data`** — los datos sobreviven a `docker compose stop/up`. Se borran solo con `docker compose down -v`.
- **`healthcheck`** — es la pieza más importante del arranque. El fallo nº1 de estas pruebas es Laravel intentando migrar antes de que MySQL acepte conexiones (MySQL tarda ~10-20 s en estar listo la primera vez). El healthcheck marca el contenedor como `healthy` solo cuando `mysqladmin ping` responde, y los demás servicios esperan a ese estado (ver `depends_on` abajo).
- **`3307:3306`** — se expone al host para poder conectar un cliente nativo (TablePlus, DBeaver). Se usa 3307 y no 3306 porque en la máquina de desarrollo había otro MySQL ocupando 3306. En una máquina limpia se puede volver a `3306:3306`.

### `backend`

```yaml
backend:
  build: ./backend
  command: php artisan serve --host=0.0.0.0 --port=8000
  environment:
    APP_KEY: ${APP_KEY:-base64:...}
    DB_HOST: mysql
    CACHE_STORE: file
    SESSION_DRIVER: file
    QUEUE_CONNECTION: sync
    SIMULATOR_URL: http://simulator:8001
    ...
  ports:
    - "8000:8000"
  depends_on:
    mysql:
      condition: service_healthy
```

- **`depends_on: mysql: condition: service_healthy`** — no arranca hasta que el healthcheck de MySQL pasa. Esto, junto con el retry del entrypoint, garantiza que las migraciones nunca corran contra una BD que aún no acepta conexiones.
- **`command: php artisan serve`** — sobreescribe el `CMD` del Dockerfile (que es `php-fpm`). `artisan serve` levanta el servidor HTTP embebido de PHP: suficiente y simple para la demo, sin necesidad de un nginx delante. El Dockerfile queda preparado para producción (php-fpm) pero para la demo se usa el servidor simple. Ver más abajo.
- **`DB_HOST: mysql`** — dentro de la red Docker, cada servicio es alcanzable por su nombre. El backend conecta a `mysql:3306`, no a `localhost`.
- **`SIMULATOR_URL: http://simulator:8001`** — así el backend delega en el micro por su nombre de red.
- **`CACHE_STORE / SESSION_DRIVER / QUEUE_CONNECTION` = `file` / `file` / `sync`** — el proyecto es una **API stateless por token** (Sanctum Bearer). No usa sesiones ni cola. Los drivers por defecto de Laravel (`database`) necesitarían tablas `cache`, `sessions` y `jobs` que no existen en este esquema (solo migramos `users`, `services`, `tracking` y los tokens de Sanctum). Usar drivers `file`/`sync` evita esas tablas y cualquier fallo de arranque, sin perder nada que la app necesite.
- **`APP_KEY`** — clave fija por defecto para que el arranque sea inmediato. En producción sería un secreto por entorno.

### `simulator`

```yaml
simulator:
  build: ./simulator
  environment:
    DB_HOST: mysql
    SIMULATOR_PORT: 8001
    ...
  ports:
    - "8001:8001"
  depends_on:
    mysql:
      condition: service_healthy
    backend:
      condition: service_started
```

- **`depends_on: backend: service_started`** — el micro solo necesita que las tablas existan cuando reciba una petición; arrancar tras el backend (que las migra) evita una carrera al primer `/generate`.
- **`8001:8001`** — el plan original dejaba el micro **sin puerto expuesto** (interno, el backend es la única puerta). Se expone durante la fase de pruebas para poder lanzarle `curl` directo (`/generate`, `/simulation/start`) sin pasar por auth. Para una entrega "de producción" bastaría comentar esta línea.

### `frontend`

```yaml
frontend:
  build: ./frontend
  ports:
    - "8090:80"
  depends_on:
    - backend
```

- **`8090:80`** — nginx sirve en el 80 del contenedor; se mapea a 8090 en el host (8080 estaba ocupado por otro proceso en la máquina de desarrollo).

### `adminer`

```yaml
adminer:
  image: adminer:latest
  ports:
    - "8091:8080"
  environment:
    ADMINER_DEFAULT_SERVER: mysql
  depends_on:
    mysql:
      condition: service_healthy
```

- **Por qué está en el compose:** para que el revisor pueda inspeccionar las tablas `services` y `tracking` **sin instalar ningún cliente de MySQL**. Es una conveniencia de revisión; la aplicación no depende de Adminer.
- **`ADMINER_DEFAULT_SERVER: mysql`** — precarga el nombre del servidor en el formulario de login para que el revisor solo tenga que meter usuario/contraseña.

---

## `backend/Dockerfile` — explicado

```dockerfile
FROM php:8.4-fpm-alpine

RUN apk add --no-cache curl libpng-dev libzip-dev oniguruma-dev zip unzip
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/backend

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .

# Crea los directorios escribibles ANTES de cualquier comando artisan
RUN mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

RUN composer run-script post-autoload-dump

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
```

Por qué cada bloque:

- **`php:8.4-fpm-alpine`** — imagen oficial de PHP 8.4, variante Alpine (imagen pequeña). FPM es el estándar de producción para servir PHP tras un nginx.
- **Extensiones (`pdo_mysql`, `mbstring`, `bcmath`, …)** — las que Laravel 12 y la conexión a MySQL necesitan. Se instalan explícitamente porque la imagen base viene mínima.
- **`composer install` con `composer.json` copiado antes que el código** — aprovecha la caché de capas de Docker: si el código cambia pero no las dependencias, no se reinstala todo. `--no-dev` porque en producción no hacen falta las dev-deps (Pest, etc.); para correr los tests se usa `composer install` completo dentro del contenedor o el propio `artisan test` con las dev-deps presentes (ver Testing).
- **Orden `mkdir/chmod` ANTES de `post-autoload-dump`** — este fue un **bug corregido**: `composer post-autoload-dump` ejecuta `artisan package:discover`, que escribe en `bootstrap/cache`. Si ese directorio no existe o no es escribible en ese momento, el build falla con *"The bootstrap/cache directory must be present and writable"*. La versión inicial creaba el directorio **después**; se reordenó para crearlo antes.
- **`ENTRYPOINT docker-entrypoint.sh` + `CMD php-fpm`** — el entrypoint prepara la BD y luego ejecuta el `CMD` (o el `command:` del compose). El `CMD` por defecto es `php-fpm` (producción); el compose lo sobreescribe por `artisan serve` para la demo.

### `backend/docker-entrypoint.sh`

```sh
#!/bin/sh
set -e
until php artisan migrate --force 2>/dev/null; do
    echo "Waiting for database..."
    sleep 2
done
php artisan db:seed --force
exec "$@"
```

- **`until migrate; do sleep; done`** — retry suave sobre las migraciones. El healthcheck de MySQL ya garantiza que la BD acepta conexiones, pero este bucle es un cinturón de seguridad extra por si el primer intento coincide con MySQL terminando de inicializar.
- **`db:seed --force`** — crea el usuario demo (`demo@demo.com` / `password`) sin pedir confirmación (`--force` es obligatorio en entornos no interactivos).
- **`exec "$@"`** — reemplaza el proceso por el comando final (`artisan serve` o `php-fpm`), de modo que las señales (Ctrl-C, `docker stop`) lleguen correctamente al servidor.

---

## `simulator/Dockerfile` — explicado

```dockerfile
FROM php:8.4-cli-alpine

RUN apk add --no-cache curl libzip-dev oniguruma-dev zip unzip
RUN docker-php-ext-install pdo pdo_mysql mbstring zip bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .

EXPOSE 8001
CMD ["php", "bin/server.php"]
```

- **`php:8.4-cli-alpine`** (no `fpm`) — el micro **no** es una app request-response servida por FPM; es un **proceso residente** ReactPHP que se ejecuta con la CLI de PHP (`php bin/server.php`) y se queda vivo escuchando HTTP y disparando el timer. Por eso la imagen base es `cli`, no `fpm`.
- **Extensiones mínimas** — solo `pdo_mysql` (persistencia) y las básicas. El dominio DDD no necesita nada más.
- **`CMD php bin/server.php`** — arranca el event loop de ReactPHP: servidor HTTP en el 8001 + un `addPeriodicTimer(5s)` que dispara el `TickHandler`. Un solo proceso, estado en memoria.

---

## `frontend/Dockerfile` — explicado (multi-stage)

```dockerfile
# Etapa 1: compilar el Vue a estáticos
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
ENV VITE_USE_MOCK=false
RUN npm run build-only

# Etapa 2: servir los estáticos con nginx
FROM nginx:alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
```

- **Multi-stage** — la etapa 1 usa Node (pesado, con todo el toolchain de build) solo para compilar; la imagen final es la etapa 2 (nginx + ~200 KB de estáticos). Node no viaja a la imagen final → imagen pequeña y sin superficie de ataque innecesaria.
- **`npm ci`** (no `npm install`) — instalación reproducible desde `package-lock.json`, más rápida y determinista en CI/build.
- **`ENV VITE_USE_MOCK=false`** — Vite fija las variables de entorno **en tiempo de compilación** (no en runtime). Se pone antes del build para que el bundle apunte a la API real en vez de a los datos mock. El `baseURL` se queda en el relativo `/api/v1`, que nginx proxya.
- **`npm run build-only`** (no `npm run build`) — `build` ejecuta también `vue-tsc` (type-check), que es lento y puede fallar por temas de tipos que no afectan al runtime. Para un build de contenedor fiable se usa `build-only` (solo `vite build`). El type-check se puede correr aparte en desarrollo.
- **`vite dev` descartado** — servir con el dev server de Vite en el contenedor daría problemas de HMR/websockets en Docker y una imagen grande. El build estático es lo "prod-like" que se espera.

### `frontend/nginx.conf` — el proxy que elimina el CORS

```nginx
server {
    listen 80;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;   # SPA fallback
    }

    location /api/ {
        proxy_pass http://backend:8000;       # proxy al backend
        proxy_set_header Host $host;
        ...
    }
}
```

- **`try_files ... /index.html`** — es una SPA con Vue Router. Cualquier ruta que no sea un fichero estático (p. ej. `/login`) se sirve con `index.html` para que el router del cliente la resuelva. Sin esto, recargar en `/dashboard` daría 404.
- **`location /api/ → proxy_pass http://backend:8000`** — esta es la decisión clave de integración. El navegador hace todas las llamadas a `http://localhost:8090/api/...` (mismo origen que la web), y nginx las reenvía internamente al backend. Como el navegador nunca ve un origen distinto, **no hay CORS que configurar**. Es la fuente nº1 de fricción en estas pruebas, eliminada de raíz.

---

## Ejecutar los tests dentro de Docker

```sh
docker compose exec backend php artisan test      # 35 tests Pest (backend)
docker compose exec simulator vendor/bin/phpunit   # 28 tests PHPUnit (dominio DDD del micro)
```

> Nota: la imagen del backend se construye con `--no-dev` (sin Pest) para producción. Para correr los tests dentro del contenedor, o bien se instalan las dev-deps (`composer install`) en el contenedor, o se corren en una imagen de test. En CI lo habitual es un paso `composer install && php artisan test` sobre el código, con SQLite `:memory:` (configurado en `phpunit.xml`), sin necesidad de MySQL.

---

## Puertos — resumen y cómo cambiarlos

| Servicio | Host | Contenedor | Por qué no el canónico |
|---|---|---|---|
| frontend | **8090** | 80 | 8080 ocupado en la máquina de desarrollo |
| backend | 8000 | 8000 | — |
| simulator | 8001 | 8001 | interno; expuesto para pruebas con `curl` |
| adminer | 8091 | 8080 | — |
| mysql | **3307** | 3306 | 3306 ocupado en la máquina de desarrollo |

Para volver a los puertos estándar en una máquina limpia, edita la sección `ports:` de cada servicio en [`../docker-compose.yml`](../docker-compose.yml). Ningún puerto interno cambia — solo el lado izquierdo (host) del mapeo.

---

## Resumen de las decisiones de Docker

1. **Healthcheck de MySQL + `depends_on: service_healthy`** → arranque fiable, sin carreras de migración.
2. **Migraciones + seeder en el entrypoint** → "un solo comando" de verdad; no hay pasos manuales.
3. **Proxy nginx `/api`** → sin CORS.
4. **Frontend multi-stage → build estático** → imagen pequeña, arranque instantáneo, prod-like.
5. **Drivers `file`/`sync` en el backend** → API stateless por token; cero tablas de infraestructura extra.
6. **Micro con imagen `cli` (proceso residente ReactPHP)** → HTTP + timer en un proceso.
7. **Adminer incluido** → el revisor inspecciona la BD sin instalar nada.
8. **Defaults en el compose (`${VAR:-default}`)** → funciona sin crear ningún `.env`.
