# Estado del proyecto — Progreso por fases

Última actualización: **2026-07-15** (post-Fase 2: backend completo + tests en verde)

---

## Resumen rápido

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Esqueleto monorepo + scaffold frontend | ✅ Completada |
| 1 | Frontend Vue 3 con dashboard completo y datos mock | ✅ Completada |
| 2 | Backend Laravel 12 con Sanctum, migraciones y tests | ✅ Completada |
| 3 | Microservicio DDD con ReactPHP y simulación GPS | ⏳ Pendiente |
| 4 | Integración Docker Compose + wiring end-to-end | ⏳ Pendiente |

---

## ✅ Fase 0 — Esqueleto del repo

**Entregable:** estructura base del monorepo lista para las fases siguientes.

- [x] `live-tracking/` como raíz del monorepo
- [x] `frontend/` scaffoldeado con `npm create vue@latest` (Vite + Pinia + Router + TS)
- [x] `docs/` con este sistema de documentación
- [x] `.gitignore` raíz (exluye `node_modules`, `vendor`, `dist`, `.env`)

---

## ✅ Fase 1 — Frontend (Commit 1)

**Entregable:** dashboard funcional completo contra datos mock. El commit marca el hito; la UI tiene toda la funcionalidad prevista.

### Vistas

- [x] **`LoginView.vue`** — dos paneles (navy izquierda + formulario derecha), credenciales demo prefijadas, responsive (stack en móvil < 700px), spinner en submit.
- [x] **`DashboardView.vue`** — shell de la aplicación con sidebar + workspace.

### Componentes

- [x] **Sidebar colapsable** (260px ↔ 60px) con secciones FLOTA / OPERACIONES / SISTEMA, sub-items expandibles (Live Tracking → Mapa/Vehículos/Conductores/Rutas), badges de notificaciones (3) y soporte (2), perfil de usuario con logout.
- [x] **`ServicePanel.vue`** — panel izquierdo con título, buscador (filtra por nombre de línea, BUS-XXX y modelo de bus), chips de filtro (Todos/Activos/Inactivos), lista scrollable, footer con timestamp de último refresh.
- [x] **`ServiceListItem.vue`** — tarjeta de bus con: identificador BUS-XXX, modelo de vehículo real (array de 8 buses europeos), badge de estado (En ruta / Disponible), grid Salida/Llegada. Estado activo: borde azul + icono de selección + fondo tenue.
- [x] **`SimulatorControls.vue`** — input numérico (1-50), botón "Generar buses" con spinner, badge de simulación activa + botón "Detener" / botón "Iniciar simulación".
- [x] **`LiveMap.vue`** — mapa Leaflet con OSM, marcadores DivIcon (blanco/azul), polyline animada de dos capas (blanca base + azul punteada con `stroke-dashoffset`), popup de bus personalizado (cabecera azul, badge de estado, ventana horaria, GPS, puntos GPS, botones Historial y Limpiar).

### Stores Pinia

- [x] **`auth.ts`** — login/logout, token en localStorage, interceptor Axios Bearer, guard de ruta.
- [x] **`services.ts`** — lista de servicios, `selectedId`, acción `generate(count)`, `selectService()`.
- [x] **`tracking.ts`** — `positions: Map<number, TrackingPoint>`, `gpsCount: Map<number, number>`, `startSimulation()` / `stopSimulation()`, `clearService(id)`, timer de 2s por tick en modo mock, `boundsInitialized` flag (evita re-zoom en cada tick).

### Utilidades

- [x] **`src/utils/polyline.ts`** — encode/decode de Google Encoded Polyline (~30 líneas), sin dependencia externa.
- [x] **`src/fixtures/routes.ts`** — 16 rutas reales de Barcelona obtenidas de OSRM (pre-baked, sin llamada en runtime). Regenerables con `node scripts/fetch-routes.mjs`.
- [x] **`frontend/scripts/fetch-routes.mjs`** — script de generación de fixtures: llama a `router.project-osrm.org` para los 16 trazados, thinea a 30 puntos y vuelca `routes.ts`.
- [x] **`src/composables/useToast.ts`** — sistema de toasts (éxito / error / info).

### Comportamiento implementado

- [x] Seleccionar bus → polyline del servicio dibujada + `fitBounds` al primer dibujado (no en cada tick).
- [x] Cambiar selección → polyline anterior eliminada, nueva dibujada.
- [x] Deseleccionar → solo marcadores en el mapa.
- [x] Popup de bus: contenido actualizado en cada tick (posición, GPS count, ventana horaria).
- [x] Botón "Limpiar" en popup → elimina el servicio del tracking y del mapa.
- [x] Búsqueda funcional: filtra por nombre de línea, código BUS-XXX y modelo de bus.
- [x] Chips de filtro Activos/Inactivos funcionan con el estado de la simulación.
- [x] Mock mode activo por defecto (`VITE_USE_MOCK=true`).
- [x] Polylines siguen calles reales (OSRM), no líneas rectas entre puntos.
- [x] Marcadores de bus aparecen inmediatamente al generar servicios (sin esperar el poll de 20s).
- [x] Lista de servicios ordenada por id de bus (BUS-001, BUS-002…).
- [x] Toast por cada bus generado: "Se ha generado el servicio del bus BUS-001 — Iveco Urbanway 12".
- [x] Contador "X rutas disponibles" + estado "Catálogo completo" cuando se agotan las 16 rutas.

---

## ✅ Fase 2 — Backend Laravel 12

**Objetivo:** API REST completa con Sanctum, migraciones, seeder y tests Pest.

Implementada en `backend/`. El backend implementa estrictamente el contrato `docs/openapi.yaml` (Spec Driven Development).

### Checklist

- [x] Estructura Laravel 12 en `backend/` con `composer.json` (PHP 8.4 + Sanctum + Pest)
- [x] Migraciones: `users`, `personal_access_tokens`, `services`, `tracking` + índice compuesto `(service_id, id)`
- [x] Seeder: usuario demo `demo@demo.com` / `password`
- [x] Modelos: `User` (HasApiTokens), `Service`, `TrackingPoint` (tabla `tracking`, append-only)
- [x] API Resources: `ServiceResource` (summary sin polyline / detail con polyline), `TrackingPointResource`
- [x] Form Request: `GenerateServicesRequest` (count: integer, 1–50)
- [x] Controladores: `AuthController`, `ServiceController`, `TrackingController`, `SimulationController`
- [x] `Controller.php` base (Laravel 12 no lo genera automáticamente — añadido manualmente)
- [x] `SimulatorClient.php` — cliente HTTP hacia el microservicio (`Http::fake()` en tests)
- [x] **35 tests Pest en verde** (Auth ×8, Service ×12, Tracking ×9, Simulation ×6) — ejecutados en Docker
- [x] `backend/Dockerfile` + `docker-entrypoint.sh`
- [x] `config/services.php` con `SIMULATOR_URL`, `config/cors.php` con `FRONTEND_URL`
- [x] Factories: `UserFactory`, `ServiceFactory`, `TrackingPointFactory`
- [x] `phpunit.xml` configurado con SQLite `:memory:` para tests

### Tests Pest — detalle por suite

| Suite | Tests | Qué valida |
|---|---|---|
| `AuthTest` | 8 | login ok/ko, validación email+password, logout revoca token (usa `createToken()` real), `me` devuelve usuario, 401 sin token en rutas protegidas |
| `ServiceTest` | 12 | 401 sin token, lista vacía, shape de `ServiceSummary` sin polyline, orden por id, detalle con polyline, 404 inexistente, delegación a SimulatorClient con `Http::fake()`, validación count (requerido / min 1 / max 50) |
| `TrackingTest` | 9 | 401 sin token, array vacío sin datos, **última posición por servicio** (varios inserts → solo el más reciente), un punto por servicio con múltiples servicios, shape exacto del OpenAPI spec, histórico completo, paginación `after_id`, 404 servicio inexistente |
| `SimulationTest` | 6 | 401 sin token en start/stop/status, delegación start/stop/status a SimulatorClient con `Http::fake()` |

### Decisiones técnicas (SDD)

- **SDD aplicado:** cada Resource, Controller y test valida el shape exacto del OpenAPI spec en `docs/openapi.yaml`. Los tests son la prueba de contrato entre el frontend y el backend.
- **Bearer tokens** (Sanctum Personal Access Tokens) — CORS simple con un header; se descartaron cookies SPA por fricción en Docker con puertos distintos.
- **`/tracking/latest`** usa `MAX(id) GROUP BY service_id` con índice `(service_id, id)` — index-scan sin full scan.
- **SimulatorClient** delega generate/start/stop/status al microservicio DDD. En tests: `Http::fake()` para no depender del micro.
- **`TrackingPoint.$timestamps = false`** — solo `created_at`, tabla append-only por diseño.
- **SQLite `:memory:`** en tests — 0 dependencias externas, tests corren en <1s.
- **Fix logout test:** `actingAs($user, 'sanctum')` no crea token real → `currentAccessToken()` retorna null. Se usa `createToken()->plainTextToken` + `withToken()` para tener un token Sanctum real que se puede revocar.

### Para ejecutar

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test      # 35 tests, todos en verde
```

---

## ⏳ Fase 3 — Microservicio DDD (PHP 8.4 + ReactPHP)

**Objetivo:** microservicio que genera servicios ficticios y simula GPS con arquitectura DDD limpia.

Pendiente de implementar.

### Checklist previsto

- [ ] Scaffold Composer PSR-4, PHPUnit
- [ ] `Domain/Model/Coordinate.php` — VO con validación de rangos
- [ ] `Domain/Model/Route.php` — VO con interpolación `pointAtDistance(meters)`
- [ ] `Domain/Model/Service.php` — Entidad
- [ ] `Domain/Model/VehicleRun.php` — Agregado con `advance(seconds)`
- [ ] `Domain/Service/PolylineCodec.php` — encode/decode con tests round-trip
- [ ] `Domain/Service/GpsNoise.php` — ruido gaussiano acotado ~±10m
- [ ] `Domain/Service/ServiceFactory.php` — genera N servicios desde `RouteCatalog`
- [ ] `Domain/RouteCatalog.php` — banco de 16 polylines reales de Barcelona (mismas rutas OSRM del frontend)
- [ ] Interfaces de repositorio en el dominio
- [ ] Handlers de Application (Generate, Start, Stop, Tick)
- [ ] Infrastructure: PDO repositories, ReactPHP HTTP server, timer
- [ ] Tests unitarios del dominio (PHPUnit, sin BD)
- [ ] `simulator/Dockerfile`

---

## ⏳ Fase 4 — Integración Docker Compose

**Objetivo:** `docker compose up -d --build` levanta todo; frontend apunta a la API real.

Pendiente de implementar.

### Checklist previsto

- [ ] `docker-compose.yml` raíz: mysql → backend → simulator → frontend
- [ ] Healthcheck MySQL + `depends_on: service_healthy`
- [ ] Entrypoint del backend: espera BD → `migrate --force` → `db:seed --force`
- [ ] Proxy nginx `/api` → backend (sin CORS)
- [ ] Variables de entorno consolidadas (`.env.example` raíz)
- [ ] Frontend: `VITE_USE_MOCK=false` + `VITE_API_URL` apunta al proxy
- [ ] Prueba end-to-end desde clon limpio
- [ ] README final con GIF o screenshot del dashboard funcionando

---

## Checklist de entrega final

- [ ] Clon limpio + `docker compose up -d --build` levanta todo sin tocar nada
- [ ] Login con `demo@demo.com` / `password` funciona
- [ ] Generar N servicios → aparecen en lista con BUS-XXX, modelo, horario
- [ ] Ejecutar simulación → marcadores aparecen y se mueven en ≤30s
- [ ] Seleccionar servicio → solo su polyline; demás buses siguen moviéndose
- [ ] Cambiar selección → polyline anterior desaparece; deseleccionar → solo marcadores
- [ ] Detener simulación → inserts cesan
- [ ] `tracking` conserva histórico completo (append-only, sin updates)
- [ ] Los 7+ endpoints responden con códigos HTTP correctos (401 sin token)
- [ ] Tests en verde: `php artisan test` (backend), `phpunit` (simulator), `npm run test` (frontend)
- [ ] 4 commits-hito presentes, en orden, con mensajes Conventional Commits
- [ ] README verificado paso a paso desde clon limpio
- [ ] Repo público accesible en incógnito
- [ ] `.gitignore` correcto (sin `node_modules`, `vendor`, `dist`, `.env`)
