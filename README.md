# Live Tracking — Plataforma de monitorización de flotas

Plataforma de seguimiento de flotas en tiempo real.
Monorepo con tres proyectos independientes orquestados con un único comando Docker.

- **`frontend/`** — Vue 3 + Vite + Pinia. Dashboard con mapa Leaflet/OpenStreetMap. ✅ *Fase 1 completa.*
- **`backend/`** — Laravel 12 + Sanctum (API REST + orquestación). ✅ *Fase 2 completa.*
- **`simulator/`** — Microservicio PHP 8.4 DDD con ReactPHP. ⏳ *Fase 3 pendiente.*

---

## Quickstart (Fase 1 — Frontend con datos mock)

```sh
cd frontend
npm install
npm run dev
```

Abre `http://localhost:5173`. Login: **`demo@demo.com`** / **`password`**.

> El frontend funciona de forma autónoma con datos mock (`VITE_USE_MOCK=true`, valor por defecto).
> Al llegar a la Fase 4, basta con `VITE_USE_MOCK=false` para apuntar al backend real.

---

## Backend (Fase 2 — Laravel 12)

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed          # crea demo@demo.com / password
php artisan serve            # http://localhost:8000
php artisan test             # 35 tests — todos en verde
```

> Requiere PHP 8.4 + SQLite (para tests) o MySQL 8 (para desarrollo/producción).
> La variable `SIMULATOR_URL` en `.env` apunta al microservicio (Fase 3); si no está levantado,
> los endpoints de simulation devolverán error 502 pero el resto de la API funciona.

---

## Arranque completo (Fase 4 — pendiente)

```sh
docker compose up -d --build
```

→ `http://localhost:8080` — sin tocar nada más.

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

```sh
# Frontend
cd frontend && npm run test:unit    # codec de polyline (round-trip + vector oficial Google)

# Backend — sin Docker, SQLite :memory: (PHP 8.4 requerido)
cd backend && php artisan test      # 23 tests en verde

# Backend — con Docker (Fase 4)
docker compose exec backend php artisan test

# Simulator (Fase 3)
docker compose exec simulator vendor/bin/phpunit
```

---

## Documentación del proyecto

| Documento | Contenido |
|---|---|
| [`docs/01-genesis.md`](docs/01-genesis.md) | Origen del proyecto, por qué se usó Fable para el planning, el prompt enviado |
| [`docs/02-design.md`](docs/02-design.md) | Diseño UI con Stitch, por qué Stitch y no Figma/v0/otros, sistema de diseño LiveTrack Narrative |
| [`docs/03-decisions.md`](docs/03-decisions.md) | Registro de decisiones técnicas: qué, por qué, alternativa descartada, impacto |
| [`docs/04-progress.md`](docs/04-progress.md) | Estado actual fase a fase, checklist de entrega final |

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
| Refresco frontend | Polling 20s | El enunciado lo pide literalmente; WebSockets sería sobreingeniería no pedida |
| Auth Sanctum | Tokens Bearer | El modo cookie falla en Docker por CORS/CSRF entre puertos distintos |
| Rutas GPS | Fixtures Barcelona pre-codificadas | Sin API externa ni random walk; creíbles visualmente y deterministas |
| Micro runtime | ReactPHP | HTTP + timers en un solo proceso; Swoole requiere extensión C |
| Datos Fase 1 | Mock mode con env var | Frontend funcional antes de que exista el backend — el mock ya implementa el spec |

---

## Limitaciones conocidas (y mejoras documentadas)

- **Estado de simulación en memoria:** si el contenedor del micro se reinicia, la simulación se detiene. Mejora: tabla `simulation_runs`.
- **Polling vs WebSockets:** el refresco de 20s produce saltos visibles. Mejora: SSE o WebSockets (~50 líneas de cambio).
- **MySQL compartido:** un purista de microservicios pediría BD por servicio. El enunciado define tablas únicas; la frontera se disciplina por convención (el micro es el único que escribe).

---

## Credenciales demo

| Campo | Valor |
|---|---|
| Email | `demo@demo.com` |
| Password | `password` |
