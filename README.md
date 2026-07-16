# Live Tracking — Plataforma de monitorización de flotas

Plataforma de seguimiento de flotas en tiempo real.
Monorepo con tres proyectos independientes orquestados con un único comando Docker.

- **`frontend/`** — Vue 3 + Vite + Pinia. Dashboard con mapa Leaflet/OpenStreetMap. ✅
- **`backend/`** — Laravel 12 + Sanctum (API REST + orquestación). ✅
- **`simulator/`** — Microservicio PHP 8.4 DDD con ReactPHP. ✅

---

## Quickstart

Requiere solo **Docker Desktop**. No hace falta instalar PHP, Node ni MySQL.

```sh
docker compose up -d --build
```

Se levantan 5 contenedores (MySQL, backend, simulador, frontend, Adminer) y las migraciones + seeder del backend corren automáticamente. Cuando el healthcheck de MySQL pasa, todo está listo.

Abre el navegador en:

| URL | Qué es |
|---|---|
| **http://localhost:8090** | **Dashboard principal** — login: `demo@demo.com` / `password` |
| http://localhost:8091 | Adminer — inspector web de la base de datos (ver más abajo) |
| http://localhost:8000 | API Laravel (para probar endpoints con `curl`) |
| http://localhost:8001 | Microservicio (para probar `/generate`, `/simulation/*` sin auth) |

Para detener todo: `docker compose down` (añade `-v` si quieres borrar también la BD).

> **Nota sobre los puertos.** Se usan `8090`/`8091`/`3307` en lugar de los canónicos `8080`/`3306` porque en la máquina de desarrollo había otros procesos ocupándolos. Si en tu equipo esos puertos están libres y prefieres los estándar, edita la sección `ports:` del [`docker-compose.yml`](docker-compose.yml). El detalle está en [`docs/05-docker.md`](docs/05-docker.md).

---

## Inspeccionar la base de datos

Se incluye **Adminer** (cliente web de MySQL) en el compose para que se puedan revisar las tablas sin instalar ningún cliente. Abre **http://localhost:8091** y entra con:

| Campo | Valor |
|---|---|
| Sistema | MySQL |
| Servidor | `mysql` (ya prefijado) |
| Usuario | `laravel` |
| Contraseña | `secret` |
| Base de datos | `live_tracking` |

Tablas relevantes:

- **`services`** — un registro por cada bus/servicio generado (nombre, horario, ruta codificada).
- **`tracking`** — tabla **append-only**: cada tick del simulador (5s) añade una fila por bus activo. Nunca se hace UPDATE ni DELETE — es el histórico completo del recorrido, tal como pide el enunciado.
- `users`, `personal_access_tokens` — auth de Laravel Sanctum.

> Truco: deja Adminer abierto en la tabla `tracking`, ve al dashboard en :8090, pulsa **Iniciar simulación** y recarga Adminer — verás filas nuevas cada 5 segundos en tiempo real.

Alternativa por si prefieres un cliente nativo (TablePlus, DBeaver, DataGrip…): el MySQL está expuesto en `localhost:3307` con los mismos credenciales.

---

## Flujo de prueba end-to-end

1. Login en http://localhost:8090 con las credenciales demo.
2. Escribe un número (p. ej. `5`) → **Crear servicios**. Aparecen en la lista **y su marcador ya sale en el mapa** (posición inicial). Puedes crear tantos lotes como quieras: la numeración continúa (BUS-006, BUS-007…) y las rutas no se repiten idénticas.
3. Pulsa **Iniciar simulación**. El mapa refresca las posiciones cada 20s (según el enunciado); en BD la tabla `tracking` crece más rápido, cada 5s (el tick del simulador).
4. Haz clic en un bus de la lista → se dibuja **solo su ruta** en el mapa. Al deseleccionar, desaparece.
5. Haz clic en un marcador → popup con estado y ubicación. El botón **Historial** dibuja el trazado GPS ya recorrido por ese bus (usa el endpoint de histórico).
6. Pulsa **Detener**. Los inserts en `tracking` cesan pero el histórico queda intacto (append-only).

---

## Cómo se construyó — herramientas y flujo de trabajo

El proyecto se desarrolló con un flujo asistido por IA en tres etapas, cada una con la herramienta adecuada. Los prompts concretos están documentados en los docs enlazados.

| Etapa | Herramienta | Para qué | Prompt / detalle |
|---|---|---|---|
| **1. Planning** | **Fable (Claude Fable 5)** | Producir el plan de ejecución fase a fase, decisiones técnicas justificadas, diseño de BD, contrato de API y estrategia de Docker **antes** de escribir código | [`docs/01-genesis.md`](docs/01-genesis.md) |
| **2. Diseño UI** | **Stitch** | Mockup de alta fidelidad del dashboard, login y popup — del que se extrajo la paleta y la jerarquía visual | [`docs/02-design.md`](docs/02-design.md) |
| **3. Implementación** | **Claude Code** | Escribir los tres proyectos, los tests, el Docker Compose y esta documentación, siguiendo el plan | commits + docs |

**Metodología: Spec Driven Development (SDD).** El contrato de la API ([`docs/openapi.yaml`](docs/openapi.yaml)) se escribió *primero*. El frontend (con datos mock), el backend (Resources + tests) y el microservicio se implementaron para satisfacer ese contrato, no al revés. Así los tres proyectos encajan en la integración sin sorpresas. Detalle en [`docs/03-decisions.md`](docs/03-decisions.md).

---

## Arquitectura

```
┌─────────────┐   HTTP (Axios, Bearer)   ┌──────────────────┐
│  Frontend   │ ───────────────────────► │  Backend Laravel  │
│  Vue 3+Vite │   /api/v1/...            │  (API + Sanctum)  │
│  Leaflet    │                          └───────┬──────────┘
└─────────────┘              lectura SQL         │  HTTP interno
                                                 ▼  (red Docker)
                                          ┌──────────┐  ┌──────────────────┐
                                          │ MySQL 8  │◄─│ Simulator (DDD)  │
                                          │ services │  │ PHP 8.4+ReactPHP │
                                          │ tracking │  │ genera + simula  │
                                          └──────────┘  └──────────────────┘
```

**Separación de responsabilidades:** el microservicio es el único escritor de `services` y `tracking`; el backend solo lee. El frontend consume la API del backend. El estado de simulación vive en memoria del proceso ReactPHP.

---

## API REST

El contrato completo está en [`docs/openapi.yaml`](docs/openapi.yaml) (OpenAPI 3.0).
El spec se escribió **antes** del backend — el frontend mock ya implementa el mismo shape de datos.

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/v1/auth/login` | email+password → token Bearer |
| POST | `/api/v1/auth/logout` | Revoca el token |
| GET | `/api/v1/auth/me` | Usuario autenticado |
| POST | `/api/v1/services/generate` | `{count: 1..50}` → delega en micro, devuelve servicios |
| GET | `/api/v1/services` | Lista (sin polyline) |
| GET | `/api/v1/services/{id}` | Detalle **con polyline** |
| GET | `/api/v1/tracking/latest` | Última posición de cada servicio activo |
| GET | `/api/v1/services/{id}/tracking` | Histórico paginado (`?after_id=`) |
| POST | `/api/v1/simulation/start` | Delega en micro |
| POST | `/api/v1/simulation/stop` | Delega en micro |
| GET | `/api/v1/simulation/status` | Estado del run |

---

## Tests

Con la stack levantada (`docker compose up -d`):

```sh
docker compose exec backend php artisan test    # 35 tests Pest (Auth, Services, Tracking, Simulation)
docker compose exec simulator vendor/bin/phpunit # 28 tests PHPUnit del dominio DDD (855 aserciones)
```

O directamente en el frontend:

```sh
cd frontend && npm run test:unit                 # codec polyline (round-trip + vector oficial Google)
```

---

## Docker en resumen

`docker compose up -d --build` levanta 5 servicios. Cada uno tiene su justificación (detalle completo en [`docs/05-docker.md`](docs/05-docker.md)):

| Servicio | Imagen | Puerto host | Notas de diseño |
|---|---|---|---|
| `mysql` | `mysql:8` | 3307 | Healthcheck `mysqladmin ping`; el resto espera a `service_healthy` antes de arrancar |
| `backend` | build `./backend` (php:8.4-fpm) | 8000 | Entrypoint: espera BD → `migrate --force` → `db:seed`. Sirve con `artisan serve` |
| `simulator` | build `./simulator` (php:8.4-cli) | 8001 | Proceso ReactPHP residente (HTTP + timer de 5s en un solo proceso) |
| `frontend` | build `./frontend` (multi-stage node→nginx) | 8090 | nginx sirve el build estático y hace **proxy `/api` → backend** (mismo origen → sin CORS) |
| `adminer` | `adminer:latest` | 8091 | Inspector web de la BD (conveniencia para el revisor; no lo usa la app) |

Decisiones clave del Docker (todas justificadas en [`docs/05-docker.md`](docs/05-docker.md)):
- **Healthcheck + `depends_on: service_healthy`** en MySQL — evita el fallo nº1 de estas pruebas: Laravel migrando antes de que la BD acepte conexiones.
- **Proxy nginx `/api`** — el navegador habla siempre con `localhost:8090`, así que no hay CORS que configurar.
- **Frontend como build estático** (no `vite dev`) — imagen pequeña, arranque instantáneo, prod-like.
- **Drivers `file`/`sync`** en el backend (no `database`) — es una API stateless por token; evita necesitar tablas de cache/sesión.

---

## Documentación del proyecto

| Documento | Contenido |
|---|---|
| [`docs/01-genesis.md`](docs/01-genesis.md) | Origen del proyecto, por qué se usó Fable para el planning, el prompt enviado |
| [`docs/02-design.md`](docs/02-design.md) | Diseño UI con Stitch, por qué Stitch y no Figma/v0/otros, sistema de diseño LiveTrack Narrative |
| [`docs/03-decisions.md`](docs/03-decisions.md) | Registro de decisiones técnicas: qué, por qué, alternativa descartada, impacto |
| [`docs/04-progress.md`](docs/04-progress.md) | Estado fase a fase, bugs encontrados y corregidos, checklist de entrega |
| [`docs/05-docker.md`](docs/05-docker.md) | Dossier de Docker: cada Dockerfile y el compose explicados línea a línea con su justificación |
| [`docs/openapi.yaml`](docs/openapi.yaml) | Contrato de la API (OpenAPI 3.0) — la fuente de verdad del SDD |

---

## Diseño UI — Sistema LiveTrack Narrative

El sistema visual está documentado en [`docs/02-design.md`](docs/02-design.md). Resumen:

- **Sidebar:** navy `#213145`, 260px colapsable a 60px, secciones FLOTA/OPERACIONES/SISTEMA, badges, perfil de usuario.
- **Primario:** `#004ac6` (azul) — selecciones, marcadores activos, acciones principales.
- **Canvas:** `#f4f7fb` — fondo del workspace.
- **Tipografía:** Inter + Material Symbols Outlined.
- **Tarjetas de bus:** BUS-XXX + modelo real europeo + badge estado + grid Salida/Llegada.
- **Mapa:** marcadores DivIcon circulares, polyline animada de dos capas (blanca base + azul punteada), popup personalizado con cabecera azul.

---

## Decisiones técnicas clave

El registro completo está en [`docs/03-decisions.md`](docs/03-decisions.md). Las más importantes:

| Decisión | Elección | Por qué no la alternativa |
|---|---|---|
| Metodología | Spec Driven Development — OpenAPI primero | Code-first: la documentación diverge del código; con SDD el contrato es la fuente de verdad |
| Mapa | Leaflet + OSM | Google Maps/Mapbox requieren API key — la demo fallaría al clonar |
| Codec polyline | Implementación propia (`src/utils/polyline.ts`) | ~30 líneas, sin dependencia, demostrable con tests |
| Refresco frontend | Polling 20s | El enunciado pide 20-30s; el mapa refresca a 20s. El simulador tickea a 5s (histórico más fino en BD). WebSockets sería sobreingeniería no pedida |
| Auth Sanctum | Tokens Bearer | El modo cookie falla en Docker por CORS/CSRF entre puertos distintos |
| Rutas GPS | Fixtures Barcelona pre-codificadas | Sin API externa ni random walk; creíbles visualmente y deterministas |
| Micro runtime | ReactPHP | HTTP + timers en un solo proceso; Swoole requiere extensión C |
| Datos Fase 1 | Mock mode con env var | Frontend funcional antes de que exista el backend — el mock ya implementa el spec |

---

## Limitaciones conocidas (y mejoras documentadas)

- **Estado de simulación en memoria:** si el contenedor del micro se reinicia, la simulación se detiene. Mejora: tabla `simulation_runs`.
- **Polling vs WebSockets:** el frontend sondea `/tracking/latest` cada 20s (setInterval). Mejora: SSE o WebSockets (~50 líneas de cambio) para push en vez de pull.
- **Catálogo de 16 rutas reales:** más allá de 16 servicios las rutas se reutilizan invertidas. Mejora: banco más grande + sub-tramos para variedad infinita real.
- **MySQL compartido:** un purista de microservicios pediría BD por servicio. El enunciado define tablas únicas; la frontera se disciplina por convención (el micro es el único que escribe).
- **Backend servido con `artisan serve`:** suficiente para la demo. En producción sería nginx + php-fpm (el `Dockerfile` ya está preparado para ello — ver [`docs/05-docker.md`](docs/05-docker.md)).

---

## Credenciales demo

| Campo | Valor |
|---|---|
| Email | `demo@demo.com` |
| Password | `password` |
