# Progreso por fases


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
- [x] Marcadores de bus aparecen inmediatamente al generar servicios (sin esperar al siguiente poll).
- [x] Lista de servicios ordenada por id de bus (BUS-001, BUS-002…).
- [x] Toast al generar servicios (en Fase 1 uno por bus; simplificado a un resumen en Fase 4).
- [x] ~~Contador "X rutas disponibles" + "Catálogo completo" al agotar las 16 rutas~~ → **eliminado en Fase 4**: la creación es ilimitada (offset + rutas invertidas). Ver [03-decisions.md](03-decisions.md).

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

## ✅ Fase 3 — Microservicio DDD (PHP 8.4 + ReactPHP)

**Objetivo:** microservicio que genera servicios ficticios y simula GPS con arquitectura DDD limpia.

Implementado en `simulator/`. **Único escritor** de `services` y `tracking` (el backend solo lee).

### Checklist

- [x] Scaffold Composer PSR-4 (`Simulator\`), PHPUnit 11
- [x] `Domain/Model/Coordinate.php` — VO con validación de rangos + `distanceTo()` (Haversine)
- [x] `Domain/Model/Route.php` — VO con `pointAtDistance(metres)` (interpolación lineal + clamp)
- [x] `Domain/Model/Service.php` — Entidad (id, name, TimeWindow, Route, polyline)
- [x] `Domain/Model/VehicleRun.php` — Agregado con `advance(seconds)` → nueva posición
- [x] `Domain/Service/PolylineCodec.php` — encode/decode Google Encoded Polyline (a mano)
- [x] `Domain/Service/GpsNoise.php` — ruido gaussiano (Box-Muller) acotado a ±25m
- [x] `Domain/Service/ServiceFactory.php` — genera N servicios (cicla el catálogo si N > 16)
- [x] `Domain/RouteCatalog.php` — 16 rutas reales de Barcelona (mismas OSRM del frontend)
- [x] Interfaces de repositorio en el dominio (`ServiceRepositoryInterface`, `TrackingRepositoryInterface`)
- [x] Handlers de Application (Generate, Start, Stop, Tick) + `SimulationState` en memoria
- [x] Infrastructure: `PdoServiceRepository`, `PdoTrackingRepository`, `Router` ReactPHP
- [x] `bin/server.php` — bootstrap ReactPHP (event loop + HTTP server + timer periódico de 5s)
- [x] **28 tests unitarios del dominio en verde (855 aserciones)** — PHPUnit, sin BD ni HTTP
- [x] `simulator/Dockerfile` (php:8.4-cli-alpine) + `composer.lock` para builds reproducibles

### Tests unitarios — detalle por suite

| Suite | Tests | Qué valida |
|---|---|---|
| `PolylineCodecTest` | 5 | **Ejemplo oficial de Google** (`_p~iF~ps|U…`) encode+decode, round-trip Barcelona, punto único, coordenadas negativas (Sydney) |
| `RouteTest` | 7 | ≥2 puntos obligatorio, `pointAtDistance` en extremos/medio, clamp por encima del total y negativo, longitud > 0 |
| `GpsNoiseTest` | 3 | 200 muestras dentro del cap de 25m, coordenada resultante válida, produce variación real |
| `VehicleRunTest` | 4 | `advance` devuelve Coordinate, arranca en el origen de la ruta, **circula en bucle** al cubrir la ruta completa (no se detiene), distancia acumulada crece |
| `ServiceFactoryTest` | 7 | crea el count pedido, rutas válidas, nombres no vacíos, start < end, 422 fuera de rango (0 / 51), cicla catálogo con 17 |

### Arquitectura DDD

```
Domain (0 dependencias) ← Application (casos de uso) ← Infrastructure (PDO, ReactPHP)
```

- **Dependencias hacia dentro:** el dominio no importa PDO ni ReactPHP; se testea con fakes en memoria.
- **Value Objects:** `Coordinate` (valida rangos), `Route` (≥2 puntos). **Agregado:** `VehicleRun` (identidad + ciclo de vida).
- **Repositorios como interfaces en el dominio**, implementación PDO en infraestructura.
- **Estado en memoria** (`SimulationState`): qué corre y progreso de cada vehículo. Se pierde al reiniciar el contenedor (YAGNI documentado).
- **Timer periódico único (5s)** en el bootstrap; `TickHandler` es no-op mientras no hay run activo → sin acoplar el timer a las transiciones de estado.

### API interna (red Docker, sin auth)

| Método | Ruta | Respuesta |
|---|---|---|
| GET | `/health` | `{status: "ok"}` |
| POST | `/generate` | **Array top-level** de `ServiceSummary` (201) — coincide con OpenAPI |
| POST | `/simulation/start` | `{running, service_count, message}` |
| POST | `/simulation/stop` | `{running: false, service_count: null, message}` |
| GET | `/simulation/status` | `{running, service_count}` |

### Revisión en detalle — bugs encontrados y corregidos

Tras escribir el código se hizo una **revisión de contrato contra el spec OpenAPI** (SDD) y una **prueba de integración real** (MySQL + simulador en Docker). Se encontraron y corrigieron 4 defectos:

1. **`server.php` no arrancaba (crítico):** unas clases anónimas envolvían los handlers para acoplar el timer, pero se pasaban a `Router`, cuyo constructor tipa `StartSimulationHandler`/`StopSimulationHandler` → `TypeError` en construcción, antes de `$loop->run()`. **Fix:** el timer periódico arranca una vez al boot y `TickHandler` se guarda solo con `isRunning()`; `Router` recibe los handlers reales.
2. **`/generate` devolvía `{services:[...]}`** en vez de un **array top-level** de `ServiceSummary` como exige el spec (el backend hace passthrough del body). **Fix:** el Router devuelve el array directamente.
3. **`/simulation/status` emitía `active_vehicles`/`tick_count`** en vez del campo `service_count` del schema `SimulationStatus`. **Fix:** se emite `service_count` (y `tick_count` como extra aditivo de debug).
4. **Formato de fecha y campo sobrante:** `/generate` devolvía `Y-m-d H:i:s` con `polyline`; el spec pide ISO `2026-07-15T06:00:00` y `ServiceSummary` sin polyline. **Fix:** formato `Y-m-d\TH:i:s`, sin polyline (la polyline vive en `ServiceDetail`, `GET /services/{id}`).

### Prueba de integración ejecutada (evidencia)

Con MySQL 8 + simulador en la misma red Docker:

- `/health` → `{"status":"ok"}`
- `POST /generate {count:3}` → 3 servicios persistidos, nombres con acentos correctos (Gràcia, Diagonal), fechas ISO
- `POST /simulation/start` → `{running:true, service_count:3}`
- Tras ~13s: **4 ticks × 3 servicios = 12 filas** en `tracking`, separadas 5s, posiciones avanzando por la ruta, precisión de 7 decimales
- `POST /simulation/stop` → `running:false`; **inserts cesan** (21 filas estables 12s después) → append-only preservado
- Validación: `count=0` y `count=51` → **422**; ruta desconocida → **404**; `count=17` (>16 rutas) → **201** (cicla catálogo)

### Para ejecutar

```bash
cd simulator
composer install
vendor/bin/phpunit          # 28 tests, 855 aserciones, todas en verde
```

El servidor real (`php bin/server.php`) requiere MySQL; se levanta con Docker Compose en la Fase 4.

---

## ✅ Fase 4 — Integración Docker Compose

**Objetivo:** `docker compose up -d --build` levanta todo; frontend apunta a la API real.

Implementado. Un único comando arranca los 5 contenedores (MySQL, backend, simulador, frontend, Adminer) y todo queda conectado.

### Checklist

- [x] `docker-compose.yml` raíz: mysql + backend + simulator + frontend + adminer
- [x] Healthcheck MySQL + `depends_on: service_healthy` (backend/simulator/adminer esperan a que MySQL responda antes de arrancar)
- [x] Entrypoint del backend: espera BD → `migrate --force` → `db:seed --force`
- [x] Fix `backend/Dockerfile`: crea `bootstrap/cache` **antes** de `composer post-autoload-dump` (era un orden incorrecto que rompía el build)
- [x] Proxy nginx `/api` → backend (mismo origen → sin CORS)
- [x] `frontend/Dockerfile` multi-stage: node build → nginx sirviendo estáticos, con `VITE_USE_MOCK=false` fijado en el build
- [x] Bugfixes al conectar frontend real con backend real:
  - Frontend leía `data.data` (envoltorio Laravel Resources que estos controllers no usan) → cambio a `data` directamente
  - `startSimulation()` en modo real solo activaba una bandera y ejecutaba el timer *mock*; no llamaba a `/simulation/start` → los buses no se movían. Corregido para invocar la API real.
  - Refresco del mapa fijado en 20s (extremo bajo del rango 20-30s que pide el enunciado); el tick del simulador se mantiene en 5s para un histórico más fino en BD
- [x] **Adminer** en el compose (puerto 8091) — inspector web de MySQL para el revisor
- [x] Prueba end-to-end verificada en el navegador

### Revisión final contra el enunciado (segunda pasada)

- [x] El panel mostraba un `BUS-00X` sintético + un modelo hardcodeado (array duplicado en el front que ni coincidía con el del micro); el enunciado pide mostrar el `name`. Corregido: se muestra el `name` real de la API.
- [x] Los buses **terminaban** la ruta y el micro se auto-detenía → mapa congelado y bandera "activa" mintiendo. Corregido: los buses **circulan en bucle** (nunca terminan), la simulación corre hasta "Detener".
- [x] Con refresco a 20s el mapa parecía congelado entre polls. Corregido: **animación suave** del marcador (transición CSS, sin recrear el elemento en cada tick) → los buses "conducen" en vez de teletransportarse.
- [x] El botón "Limpiar" del popup borraba el bus pero reaparecía al siguiente poll. Corregido con un set de IDs ignorados en el store.
- [x] `GET /simulation/status` reconciliado al cargar la página → la bandera `simulationRunning` refleja el estado real del micro.
- [x] El "Historial" dibujaba un punto por cada posición (cientos, superpuestos en varias vueltas). Corregido: rastro reciente limpio (últimas ~80 posiciones), línea suave.
- [x] Limpieza de comentarios placeholder (`ponytail:`) en backend y frontend.

### Bugs encontrados y corregidos al integrar

Al conectar el frontend (hasta ahora en modo mock) con el backend + simulador reales, aparecieron dos bugs que estaban ocultos:

1. **Los buses no se movían nunca (crítico).** La función `startSimulation()` del store `tracking.ts` estaba escrita para modo mock: solo activaba una bandera y arrancaba un `setInterval` con datos falsos. En modo real **jamás llamaba al endpoint `/simulation/start`**, por lo que el simulador nunca arrancaba, no se escribían filas nuevas en `tracking`, y los marcadores quedaban congelados. **Fix:** en modo real llama a la API y refresca inmediatamente.

2. **Envoltorio de respuesta inexistente.** El frontend leía `data.data` esperando un envoltorio `{data: [...]}` (el que pone Laravel Resources por defecto), pero los controllers del backend devuelven arrays JSON planos por spec OpenAPI. Sin este fix, listas y marcadores llegaban `undefined`. **Fix:** leer `data` directamente en `services.ts` y `tracking.ts`.

### Puertos expuestos

| Puerto host | Servicio | Uso |
|---|---|---|
| **8090** | frontend (nginx) | **Dashboard principal** |
| 8091 | adminer | Inspector web de MySQL |
| 8000 | backend | API REST directa (para pruebas con `curl`) |
| 8001 | simulator | API interna del micro (para pruebas sin auth) |
| 3307 | mysql | Cliente MySQL nativo (TablePlus, DBeaver…) |

> Se usan 3307/8090/8091 en lugar de los canónicos porque en la máquina de desarrollo había otros procesos (openmetadata) ocupando 3306/8080. Un evaluador con esos puertos libres puede modificar el `docker-compose.yml` si prefiere los estándar.

### Adminer — inspector de la base de datos

Adminer es un cliente MySQL web ligero (~50 MB) que se incluye en el compose para que **el revisor pueda inspeccionar `services` y `tracking` sin instalar nada**. Abre `http://localhost:8091` y usa:

- Servidor: `mysql` · Usuario: `laravel` · Contraseña: `secret` · BD: `live_tracking`

Ideal para ver en vivo cómo `tracking` va creciendo mientras la simulación corre.

### Ajustes de UX tras probar en el navegador

Tres correcciones sobre feedback de uso real:

1. **El bus no aparecía al crear el servicio** (solo la ruta al seleccionarlo). Un servicio recién creado no tenía ninguna fila en `tracking`, así que `/tracking/latest` no devolvía posición → sin marcador. **Fix:** `GenerateServicesHandler` escribe una **posición inicial** en el arranque de la ruta al generar → el marcador aparece de inmediato (estado "DISPONIBLE").
2. **Botón "Historial" del popup no hacía nada.** El manejador de clics del mapa solo cubría cerrar/limpiar. **Fix:** cableado a `showHistory()`, que llama a `GET /services/{id}/tracking` y **dibuja el trazado recorrido** (línea ámbar + puntos) haciendo zoom a él. Demuestra el endpoint de histórico.
3. **Se creaban siempre las mismas rutas repetidas.** `ServiceFactory::createMany` usaba un índice local desde 0 en cada llamada → cada lote reiniciaba en BUS-001/La Rambla. **Fix:** el handler pasa un **offset** = nº de servicios existentes (`ServiceRepositoryInterface::count()`), de modo que la numeración y las rutas continúan la secuencia. Más allá de las 16 rutas del catálogo, la ruta se **invierte** en ciclos impares (`BUS-017 La Rambla (inv)`) para que las repeticiones no sean idénticas. Se quitó el tope de 16 en el frontend → creación ilimitada (hasta 50 por lote).

### Prueba end-to-end verificada

Flujo completo probado en el navegador:

- `docker compose up -d --build` desde clon limpio → los 5 contenedores arrancan sin intervención
- Login demo → dashboard carga con los servicios existentes en BD
- Crear servicios (5) → filas nuevas en `services`, aparecen en la lista
- Iniciar simulación → simulador emite `{running:true, service_count:N}`, `tracking` crece cada 5s
- Marcadores en el mapa se actualizan cada 20s (refresco según enunciado) por rutas reales de Barcelona
- Seleccionar bus → polyline dibujada; deseleccionar → solo marcadores
- Detener → inserts en `tracking` cesan, histórico intacto (append-only)

---


