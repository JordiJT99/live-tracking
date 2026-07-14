# Génesis del proyecto — Planning con Fable

## Punto de partida: el proyecto

El proyecto es una plataforma Full Stack con estas restricciones concretas:

- **Tres proyectos independientes** en un monorepo: Frontend (Vue 3), Backend (Laravel 12), Microservicio (PHP puro DDD).
- **Un único comando** para levantar todo: `docker compose up -d --build`.
- **Mínimo 4 commits** con un orden narrativo explícito: Frontend → Backend → Microservicio → Integración.
- **MySQL compartido**: el microservicio escribe, el backend solo lee.
- El microservicio debe tener arquitectura **DDD** y usar **ReactPHP**.
- La simulación GPS debe generar ruido gaussiano real y decodificar Google Encoded Polylines.

## Por qué usar un modelo de IA para el planning

La prueba pide muchas piezas que se tocan: si el esquema de la BD no está fijo antes de escribir el frontend, los contratos de la API no cuadran; si la arquitectura DDD del micro no está clara antes de empezar, el dominio acaba contaminado de infraestructura. Empezar a codificar sin plan en una prueba de este tamaño es la forma más segura de perder horas reescribiendo.

Usar un modelo de IA para la fase de planificación tiene tres ventajas concretas:

1. **Anticipa dependencias ocultas** — el modelo razona sobre el stack completo a la vez. Identifica, por ejemplo, que el entrypoint de Docker debe esperar el healthcheck de MySQL antes de migrar, o que Sanctum en modo Bearer es más seguro en Docker que en modo cookie por problemas de CORS/dominios.
2. **Justifica cada decisión** — el resultado no es solo "usa Leaflet" sino "usa Leaflet porque Google Maps requiere API key y la prueba debe funcionar en la máquina del evaluador sin credenciales externas."
3. **Produce un contrato de API antes de escribir código** — el plan incluye la tabla completa de endpoints; el frontend se puede construir contra fixtures con el mismo shape que tendrá la API real.

## Por qué Fable y no otro modelo

Fable (Claude Fable 5) es el modelo de Anthropic especializado en razonamiento complejo, narrativa estructurada y tareas que requieren mantener muchas restricciones simultáneas en mente. Para un plan técnico donde hay que equilibrar:

- Restricciones del enunciado (orden de commits, DDD, docker único)
- Decisiones de arquitectura con justificación explícita
- Estrategia de testing proporcional (sin exagerar cobertura, sin dejar puntos ciegos)
- Estimaciones realistas de tiempo por fase

…Fable produce planes más densos y mejor razonados que un modelo genérico. La alternativa habría sido Sonnet o Haiku, que sirven para código pero tienden a planes más superficiales en problemas multi-restricción. GPT-4o hubiera roto la coherencia del ecosistema Anthropic que ya estábamos usando.

## El prompt enviado a Fable

El prompt fue una descripción del desafío técnico con instrucción de producir:

- Un plan de ejecución fase a fase (en el orden de commits exigido)
- Decisiones técnicas con tabla comparativa (qué / por qué / alternativa descartada)
- Diseño de BD con justificación de tipos y índices
- Diseño de API completo (métodos, rutas, descripción)
- Diseño del microservicio DDD (árbol de carpetas con responsabilidad de cada clase)
- Estrategia de Docker (healthchecks, dependencias, variables de entorno)
- Checklist de entrega verificable

El modelo produjo el plan completo que está en [`../.claude/plans/`](../../.claude/plans/) — un documento de ~600 líneas que cubre los 17 puntos del enunciado.

## Qué se instaló / configuró tras el plan

Con el plan en mano, la Fase 0 fue crear el esqueleto del monorepo:

```
live-tracking/
├── frontend/     ← scaffold con npm create vue@latest (Vite + Pinia + Router + TS)
├── backend/      ← pendiente (Fase 2)
├── simulator/    ← pendiente (Fase 3)
└── docs/         ← este directorio
```

`npm create vue@latest` con TypeScript, Pinia y Vue Router. Sin Vitest en el scaffold inicial — se añadirá cuando haya lógica de store que testear, no antes.
