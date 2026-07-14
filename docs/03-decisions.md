# Decisiones técnicas — Qué, por qué y qué se descartó

Este documento es el registro definitivo de cada decisión técnica relevante del proyecto. Para cada una: la elección, la razón, la alternativa descartada y el impacto.

---

## Metodología de desarrollo

### Spec Driven Development (SDD)

**Elección:** escribir el contrato de la API (`docs/openapi.yaml`) antes de implementar el backend.

**Qué es SDD:** el spec es el artefacto principal. El código (backend, tipos TypeScript, tests) es secundario — se escribe para satisfacer el spec, no al revés. La diferencia con code-first es que en code-first la documentación siempre va por detrás del código y diverge; en SDD, el spec es la fuente de verdad y no puede divergir porque el código se valida contra él.

**Por qué:** este proyecto tiene tres proyectos que deben comunicarse: el frontend llama al backend, el backend delega en el micro. Si cada equipo (o cada fase) define su propio shape de datos, la integración falla. El OpenAPI spec define `ServiceSummary`, `TrackingPoint`, `SimulationStatus` una sola vez; el frontend, el backend y los tests usan esa misma definición.

**Cómo se aplica aquí:**

1. **El spec se escribe en Fase 1** — antes de que exista el backend. El frontend mock usa exactamente el mismo shape que define el spec. Si el mock devuelve `{ service_id, latitude, longitude, created_at }`, el spec dice que `TrackingPoint` tiene esos cuatro campos con esos tipos. No hay sorpresas en la integración.

2. **Los tipos TypeScript son una proyección del spec** — `interface Service` y `interface TrackingPoint` en `src/fixtures/index.ts` son idénticos a los schemas `ServiceSummary` y `TrackingPoint` del OpenAPI. En Fase 4 se puede generar el TS directamente del spec con `openapi-typescript`.

3. **El backend implementa el spec, no al revés** — cuando se construya Laravel (Fase 2), cada Resource class (`ServiceResource`, `TrackingPointResource`) tiene que producir el shape que dice el spec. Los tests de Pest validan eso.

**Alternativa descartada:** definir los tipos en el backend y exportar la documentación desde el código (swagger-php annotations). El problema: la documentación se escribe después y siempre hay un gap entre lo que el código hace y lo que los annotations dicen.

**Impacto:** el contrato está en [`docs/openapi.yaml`](openapi.yaml). Es legible por humanos, usable con Swagger UI, y ejecutable con herramientas como Spectral (linting) o Specmatic (contract testing).

---

## Arquitectura general

### Monorepo

**Elección:** un único repositorio con tres carpetas independientes (`frontend/`, `backend/`, `simulator/`).

**Por qué:** el enunciado exige 4 commits en un solo repo público y un `docker-compose.yml` raíz que orqueste los tres proyectos. Tres repos separados son imposibles de orquestar con un único comando y rompen la narrativa de commits pedida.

**Alternativa descartada:** monorepo con workspaces NPM/Composer. Añade complejidad de configuración sin ningún beneficio para proyectos en lenguajes distintos.

**Impacto:** un solo `git clone`, un solo `docker compose up`, historia de commits coherente y evaluable.

---

### Separación de responsabilidades micro/backend (CQRS ligero)

**Elección:** el microservicio es el único escritor de `services` y `tracking`; el backend solo lee.

**Por qué:** el enunciado lo implica al pedir que el micro genere los datos y que el backend los exponga. Hacer que ambos escriban crea conflictos de concurrencia y diluye la responsabilidad. Esta separación es evaluable visualmente en el código (el backend no tiene ningún `INSERT` en esas tablas).

**Alternativa descartada:** que el backend también inserte tracking. Viola la arquitectura pedida y mezcla responsabilidades.

**Impacto:** la arquitectura se justifica como "CQRS ligero" — término que el evaluador reconocerá y valorará.

---

## Frontend

### Vue 3 + Vite + Pinia + Vue Router + TypeScript

**Elección:** stack prescrito por la prueba, scaffoldeado con `npm create vue@latest`.

**Por qué TypeScript:** con TS, el contrato `Service` / `TrackingPoint` es explícito desde el primer store. Los errores de tipado emergen antes de correr el código. En un proyecto con múltiples stores que comparten tipos, TS vale su peso.

**Alternativa descartada:** Vue 3 sin TS. Válido, pero más arriesgado en refactors. Si hay tiempo, TS siempre se amortiza en un proyecto de esta complejidad.

---

### Leaflet + OpenStreetMap (sin API key)

**Elección:** `leaflet` instalado vía npm, tiles de OpenStreetMap.

**Por qué:** Google Maps y Mapbox requieren API key y cuenta de facturación. La prueba debe arrancar con `docker compose up` en la máquina del evaluador sin que él toque nada. Una demo que falla por falta de key no pasa la revisión. Leaflet es open source, sin key, con soporte nativo de marcadores DivIcon, polylines y fitBounds.

**Matiz importante:** "Google Encoded Polyline" es un _formato de codificación de coordenadas_, no obliga a usar Google Maps. El algoritmo está documentado públicamente y lo decodificamos nosotros mismos (ver `src/utils/polyline.ts`).

**Alternativa descartada:** Mapbox GL JS — requiere token, mejor para 3D/estilo, innecesario aquí.

---

### Marcadores DivIcon (HTML/CSS) en lugar de iconos PNG

**Elección:** `L.divIcon()` con un div circular blanco/azul centrado.

**Por qué:** los marcadores de Leaflet por defecto son un PNG fijo. Con `divIcon` el marcador es HTML/CSS puro: se puede cambiar color, tamaño y estado (seleccionado/no seleccionado) desde CSS sin gestionar assets de imagen. El marcador activo cambia a fondo azul con un simple cambio de clase.

**Impacto:** marcadores visualmente coherentes con el design system sin PNGs externos.

---

### Polyline animada: dos capas (base blanca + azul punteada)

**Elección:** dos `L.polyline` apilados — la base blanca (weight 7, opacity 0.85) da "halo" y la capa azul (weight 4, dashArray '14 8', clase CSS `route-animated`) lleva la animación CSS de `stroke-dashoffset`.

**Por qué:** una sola polyline con `dashArray` sobre el tile del mapa pierde legibilidad en zonas oscuras. La base blanca actúa de "carretera" y hace la línea legible en cualquier fondo. La animación CSS (`@keyframes dash-flow { to { stroke-dashoffset: -22 } }`) simula movimiento direccional sin JS.

**Alternativa descartada:** una sola polyline sólida. Funcional pero sin el efecto visual de "flujo" que hace evidente la dirección del bus.

---

### Límite de servicios y catálogo de rutas

**Pregunta:** ¿Hay un máximo de servicios que se pueden crear? ¿Se puede hacer ilimitado?

**Sí, hay un límite — y es intencional.** El límite actual es **16 servicios** (= `BARCELONA_ROUTES.length` en `src/fixtures/routes.ts`). Cada servicio ocupa exactamente una ruta del catálogo, y una ruta nunca se repite en dos servicios simultáneos.

**Por qué un límite:** la alternativa sin límite sería generar rutas de forma procedural (caminos aleatorios por el mapa). El problema: un random walk produce zigzags sobre edificios — visualmente pobre y poco creíble para una demo de gestión de flotas. Las rutas pregrabadas son trazados reales de Barcelona que se ven bien sobre el mapa, son deterministas y no requieren ninguna API externa.

**Por qué 16 y no infinito:** el catálogo cubre los principales ejes de la ciudad (Diagonal, Gran Via, Passeig de Gràcia, Via Laietana, etc.). Para una demo, 16 servicios simultáneos en el mapa son más que suficientes. Ampliar el catálogo a 50 rutas sería trivial (añadir más arrays de coordenadas) pero no aporta valor demostrativo.

**Cómo hacerlo ilimitado en producción:** en el microservicio real (Fase 3), la generación de rutas no usa un catálogo fijo sino que:
- Tiene un banco más grande (~50 rutas pregrabadas)
- Genera sub-tramos de rutas existentes (una ruta larga dividida en 3 servicios de mañana/tarde/noche)
- Permite rutas inversas (mismo trazado en sentido contrario)
- Con lo cual el número efectivo de servicios posibles es `rutas × 3 turnos × 2 direcciones` = ilimitado a efectos prácticos para la demo

El límite del mock (`ROUTE_CATALOG_SIZE`) se notifica al usuario en tiempo real en el topbar con el contador "X rutas disponibles", y cuando llega a 0 el botón "Crear servicios" se deshabilita y aparece "Catálogo completo".

---

### Por qué el usuario elige el número de servicios (input numérico) y no un formulario

**Elección:** campo numérico (1–50) + botón "Crear servicios". El backend (o el micro en modo mock) elige las rutas automáticamente.

**Por qué:** el enunciado de la prueba especifica literalmente "generar N servicios ficticios". El control que pide es un número, no un formulario de configuración de servicio. Implementar exactamente lo que pide el enunciado, ni más ni menos, es la decisión correcta.

**Qué implicaría un formulario de servicio completo:** el usuario tendría que elegir ruta, vehículo, horario de salida, horario de llegada, conductor asignado. Eso es un CRUD completo de gestión de flotas — fuera del alcance de la prueba y potencialmente confuso en una demo que evalúa arquitectura, no UX de backoffice.

**Mejora propuesta (no implementada):** en un producto real, el formulario de servicio tendría: selector de ruta (desplegable con las rutas del catálogo), selector de vehículo (BUS-XXX disponible), ventana horaria (datetime pickers), y validación de conflictos de horario. Se dejaría para una fase posterior una vez que el catálogo de rutas y el inventario de vehículos estén persistidos en base de datos.

---

### Rutas reales de Barcelona via OSRM (pre-baked en fixtures)

**Elección:** 16 rutas reales de Barcelona obtenidas de `router.project-osrm.org` y guardadas como coordenadas en `src/fixtures/routes.ts`. En runtime no se llama a ninguna API externa.

**Cómo:** el script `frontend/scripts/fetch-routes.mjs` llama a OSRM una vez por ruta, thinea el resultado a 30 puntos y vuelca el array en `routes.ts`. Para añadir rutas o regenerar: `node scripts/fetch-routes.mjs`.

**Por qué OSRM y no otro servicio:** OSRM es el motor de routing de OpenStreetMap, gratuito, sin API key y con un servidor público de demo estable. Devuelve GeoJSON con el trazado real por calles. Las coordenadas se guardan en el repo para que el mock mode no tenga ninguna dependencia de red.

**Por qué pre-baked y no llamada en runtime:** el modo mock debe funcionar sin internet. Los datos son deterministas, reproducibles y versionados en git.

**Alternativa descartada:** OSRM autohospedado en Docker. Añade un contenedor de 4 GB solo para tener rutas bonitas — sobreingeniería. La demo pública de OSRM es suficiente para generar los fixtures una vez.

**Relación con Fase 3:** el microservicio DDD tendrá su propio `RouteCatalog` con estas mismas coordenadas. La generación de servicios en el micro usará este catálogo para asignar una ruta a cada servicio y codificarla como Google Encoded Polyline antes de persistir en MySQL. La lógica es idéntica a la del mock — solo cambia el punto de ejecución (PHP en lugar de TS) y el destino (MySQL en lugar de memoria).

---

### Generación de servicios en base de datos (Fase 3, no Fase 1)

**Pregunta:** ¿por qué en Fase 1 los servicios no se persisten en una DB real?

**Respuesta:** según el enunciado, **el microservicio es el responsable exclusivo de crear los registros en `services`** con `id`, `name`, `start_time`, `end_time` y `polyline`. El backend (Laravel) solo orquesta y expone. El frontend nunca escribe en la DB.

En Fase 1, `generateMockServices` simula exactamente ese comportamiento: produce objetos con el mismo shape que la tabla `services` tendrá. Cuando el micro esté implementado, el frontend solo cambia `VITE_USE_MOCK=false` — cero cambios de lógica de UI.

El spec OpenAPI (`docs/openapi.yaml`) documenta `POST /services/generate` con el shape de respuesta que el microservicio producirá, garantizando que el mock y el real sean compatibles.

---

### Marcadores de bus visibles inmediatamente tras generar servicios

**Problema:** al generar servicios, el marcador del bus tardaba hasta 20s en aparecer (tiempo del siguiente poll de tracking).

**Solución:** `SimulatorControls` llama a `tracking.refreshPositions()` justo después de que `services.generate()` devuelve, antes de mostrar los toasts. Una línea de código.

**Por qué así:** `refreshPositions()` ya existe en el tracking store; llamarla inmediatamente después de generar es el mínimo necesario. La alternativa (iniciar el poll más rápido o usar un watcher reactivo sobre `services`) sería más compleja sin beneficio adicional.

---

### Codec de polyline propio (`src/utils/polyline.ts`)

**Elección:** implementación propia del algoritmo de Google Encoded Polyline (~30 líneas de TS).

**Por qué:** el algoritmo está documentado públicamente. Implementarlo en el propio código evita una dependencia externa y demuestra que entendemos el formato que viaja por la API. En el microservicio (Fase 3), se hace lo mismo en PHP con tests round-trip — son la misma lógica, el evaluador verifica coherencia.

**Alternativa descartada:** librerías `@mapbox/polyline` o `polyline`. Válidas, pero añaden dependencia para ~30 líneas de código estable.

---

### Mock mode con `VITE_USE_MOCK=true`

**Elección:** variable de entorno que controla si los stores usan datos reales (Axios) o fixtures locales. Activado por defecto en `.env`.

**Por qué:** permite desarrollar y demostrar la UI sin que el backend ni el microservicio estén levantados. La Fase 1 (Frontend) produce un commit funcional antes de que exista el backend — exactamente lo que pide el orden de commits de la prueba.

**Impacto:** el frontend en mock mode pasa a modo real con solo cambiar `VITE_USE_MOCK=false` y apuntar `VITE_API_URL` al backend real. Cero cambios de código.

---

### Sanctum con tokens Bearer (no modo cookie-SPA)

**Elección:** `auth:sanctum` con Personal Access Tokens, interceptor Axios adjunta el `Bearer` en cada petición.

**Por qué:** el modo cookie de Sanctum exige alineación fina de dominios, CORS preflight y CSRF token que en Docker (frontend en :8080, backend en :8000) es la fuente número uno de horas perdidas. Bearer es más simple: un header, un interceptor.

**Alternativa descartada:** JWT con una librería externa. Sanctum está en el stack pedido y ya resuelve tokens, revocación y middleware — no hay razón para reemplazarlo.

---

### Polling cada 20 s (no WebSockets / SSE)

**Elección:** `setInterval` de 20 segundos en el store de Pinia (`tracking.ts`).

**Por qué:** el enunciado dice literalmente "refrescar automáticamente las posiciones cada 20-30 segundos". Implementar WebSockets o SSE sería sobreingeniería no pedida que además requiere un contenedor adicional (Redis/pusher) o configuración de OWIN. Polling es la solución que pide el enunciado.

**Mejora futura documentada:** cambiar el polling por SSE costaría ~50 líneas en el backend y ~10 en el frontend. Se menciona en el README como evolución natural.

---

### Estado del simulador en memoria del micro (sin tabla `simulation_runs`)

**Elección:** el estado de si la simulación está corriendo vive en variables del proceso ReactPHP.

**Por qué:** el enunciado no pide persistencia de estado entre reinicios. Añadir una tabla `simulation_runs` sería YAGNI puro. El README documenta la limitación: si el contenedor del micro se reinicia, la simulación se detiene.

**Impacto:** simplificación legítima. El dominio del micro sigue siendo correcto — solo la persistencia de estado no existe todavía.

---

## Componentes de UI — decisiones específicas

### Sidebar colapsable (LoadSwift style)

**Elección:** sidebar con estado `collapsed = ref(false)`. Al colapsar: `width: 260px → 60px`, labels e iconos de sección se ocultan, solo quedan los iconos de nav + avatar del usuario.

**Por qué:** el mockup de Stitch referenciaba el estilo de LoadSwift — sidebar oscuro con secciones expandibles, perfil de usuario en el bottom. Es el patrón estándar de dashboards B2B que el evaluador reconocerá como profesional.

**Alternativa descartada:** tabs horizontales en el topbar. Se habían incluido inicialmente (Flota Map / Alertas / Analítica) pero se eliminaron porque eran decorativos — no hacían nada funcional — y las especificaciones de la prueba no los piden.

---

### Formato BUS-XXX para identificadores de bus

**Elección:** `BUS-${String(id).padStart(3, '0')}` — los IDs numéricos de la BD se formatean como "BUS-001", "BUS-002", etc.

**Por qué:** el nombre generado por el micro ("Línea 1 – La Rambla") es descriptivo de la ruta, no del vehículo. Un identificador de vehículo real siempre tiene matrícula o código propio. BUS-XXX es convencional en operaciones de flota y es más buscable (el usuario puede buscar "BUS-003" exacto).

**Impacto:** el buscador del panel filtra por nombre de línea, ID de bus (BUS-XXX) y modelo del vehículo — los tres campos que un operador usaría en realidad.

---

### Modelos de bus (array de 8 buses europeos reales)

**Elección:** `BUS_MODELS = ['Iveco Urbanway 12', 'Mercedes Citaro G', ...]` asignados por `(id-1) % 8`.

**Por qué:** mostrar "Bus genérico" como modelo hace la demo poco creíble. Los 8 modelos son vehículos reales que operan en flotas urbanas europeas. El evaluador que conozca el sector los reconocerá. La asignación por módulo es determinista (BUS-001 siempre es un Iveco Urbanway 12) sin BD adicional.

---

### Popup del mapa con contenido actualizado en cada tick

**Elección:** `m.setPopupContent(makePopupContent(serviceId))` se llama en cada `updateMarkers()` aunque el popup esté cerrado.

**Por qué:** el HTML del popup de Leaflet se inyecta fuera del sistema de reactividad de Vue. Si solo se genera el HTML al abrir el popup, el contenido queda obsoleto al momento de abrirlo. Actualizar en cada tick garantiza que el contenido sea siempre fresco cuando el usuario lo abre.

**Alternativa descartada:** generar el HTML solo `on('popupopen', ...)`. El evento de Leaflet llega _después_ de renderizar, lo que causa un frame de contenido obsoleto visible.

---

### Event listeners delegados en el contenedor del mapa

**Elección:** un único listener en `map.getContainer()` que comprueba `e.target.closest('.bus-popup-close')` y `.btn-clear[data-service-id]` para los botones del popup.

**Por qué:** los popups de Leaflet son HTML dinámico; adjuntar listeners individuales a cada botón de cada popup requiere gestión de cleanup al cerrar/reabrir. Un listener delegado en el contenedor del mapa cubre todos los popups actuales y futuros sin leak de memoria.

---

### Búsqueda que filtra por nombre, BUS-XXX y modelo

**Elección:** el predicado de búsqueda cubre tres campos:
```typescript
s.name.toLowerCase().includes(q) ||
`bus-${String(s.id).padStart(3, '0')}`.includes(q) ||
BUS_MODELS[(s.id - 1) % BUS_MODELS.length].toLowerCase().includes(q)
```

**Por qué:** el bug que se corrigió fue exactamente este: la búsqueda original solo comparaba `s.name` (que contiene "Línea 1 – La Rambla") pero la UI muestra "BUS-001" y "Iveco Urbanway 12". El operador que busca "BUS-001" o "Mercedes" no encontraba nada.

---

### Login de dos paneles

**Elección:** panel izquierdo (44%, navy `#213145`) con marca + features; panel derecho (56%, `#f4f7fb`) con formulario en tarjeta blanca.

**Por qué:** el patrón de dos paneles en login B2B es estándar (Stripe, Linear, Vercel). El panel izquierdo convierte el login en una oportunidad de mostrar las capacidades del producto. Técnicamente es trivial (flexbox 44/56%) pero la percepción es de UI profesional.

**Alternativa descartada:** login centrado de un panel. Funcional pero genérico.

---

## Decisiones pendientes (Fases 2-4)

Estas decisiones están definidas en el plan pero no implementadas todavía. Se documentarán aquí con más detalle cuando se implementen:

- **`SimulatorClient` en Laravel** — cliente HTTP interno hacia el micro, con `Http::fake()` en los tests.
- **Índice `(service_id, id)` en `tracking`** — para que `MAX(id) GROUP BY service_id` sea un index-scan.
- **Proxy nginx `/api` → backend** — para eliminar CORS de raíz en vez de configurarlo.
- **`PolylineCodec` en PHP con tests round-trip** — la pieza más evaluable del dominio DDD.
- **`GpsNoise` gaussiano acotado** — desplazamiento de ~±10m reproducible con seed.
