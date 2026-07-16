# Diseño UI — Stitch y el sistema visual LiveTrack

## Por qué diseñar antes de codificar la UI

La tentación en cualquier proyecto nuevo es ir directo al código. El problema: si el mapa tiene el panel a la izquierda o a la derecha, si los controles del simulador van en el topbar o en el sidebar, si las tarjetas de bus son filas o cards — estas decisiones cambian el CSS y la estructura de componentes. Tomar estas decisiones sin un mockup implica reescribir estilos tres veces. Un mockup de alta fidelidad previo convierte esas decisiones de 30 minutos en 2 minutos.

## Por qué Stitch y no otra herramienta

Las alternativas evaluadas:

| Herramienta | Por qué se descartó |
|---|---|
| **Figma** | Requiere diseño manual pixel a pixel. Para un dashboard de monitorización de flota, el tiempo de diseño manual excede el valor. Útil cuando tienes un equipo de diseño dedicado. |
| **Adobe XD** | Mismo problema que Figma. Descontinuado en muchos flujos modernos. |
| **v0.dev (Vercel)** | Genera componentes React/Tailwind. El proyecto es Vue 3, y el output de v0 requiere adaptar todo a mano. |
| **Midjourney / DALL-E** | Genera imágenes, no código. El resultado no es implementable directamente. |
| **Wireframes manuales (Excalidraw)** | Fidelidad baja. El "salto" entre wireframe y código sigue siendo enorme. |

**Stitch** (herramienta de diseño de Anthropic) genera mockups de alta fidelidad a partir de un prompt de texto. La ventaja clave es que:

1. El output es HTML/CSS **implementable** — se puede extraer directamente la paleta de colores, espaciados y jerarquía visual.
2. Acepta referencias de estilo en lenguaje natural ("sidebar oscuro estilo dashboard de flotas", "tarjetas con sombra sutil").
3. El ciclo de iteración es segundos, no horas.
4. Al estar en el ecosistema Anthropic, la coherencia con el workflow de Claude Code es máxima.

## El prompt enviado a Stitch

El prompt describió la pantalla completa del dashboard con estas restricciones:

- **Panel izquierdo**: sidebar de navegación oscuro con secciones colapsables (Flota, Operaciones, Sistema), perfil de usuario en la parte inferior, badges de notificaciones
- **Topbar**: título de la vista + controles del simulador (input numérico + "Generar buses" + "Iniciar simulación")
- **Panel central izquierdo**: lista de servicios con búsqueda, chips de filtro (Todos/Activos/Inactivos), tarjetas de bus con ID (BUS-001), modelo, badge de estado y horario
- **Área principal**: mapa Leaflet con marcadores de bus circulares, polyline animada del servicio seleccionado
- **Referencia visual**: "estilo LoadSwift / dashboard de gestión de flotas, colores oscuros en sidebar, canvas claro"

También se generó el mockup de:
- La pantalla de **login** (dos paneles: marca/features a la izquierda, formulario a la derecha)
- El **popup de bus** en el mapa (cabecera azul con BUS-XXX, campos de estado/ventana horaria/GPS)

## Cómo se pasó el diseño al código

El flujo fue el siguiente:

1. Stitch generó el mockup y ofrece un botón **"Export"** / **"Download ZIP"** con el HTML y CSS del diseño.
2. Se descargó ese ZIP y se descomprimió **directamente dentro de la carpeta del proyecto** (en la raíz de `live-tracking/`).
3. Con el ZIP accesible en disco, Claude Code pudo leer los archivos, extraer la paleta de colores, los tokens CSS, la jerarquía de componentes y la estructura de bloques, y trasladarlos fielmente a los componentes Vue sin necesidad de hacer capturas de pantalla ni copiar CSS a mano.

Este paso es clave: Stitch genera HTML/CSS real, y ponerlo en el proyecto hace que Claude Code lo trate como una fuente de verdad más, igual que el OpenAPI spec o las fixtures de rutas. Sin este paso, el "traspaso" del diseño al código se haría a ojo, con riesgo de desvíos visuales.

## Iteraciones sobre el mockup

Stitch generó un primer mockup válido. Se iteró en cuatro puntos:

1. **Sidebar plano → secciones con etiquetas** — el primer sidebar era una lista plana de iconos + texto. Se añadieron las secciones FLOTA / OPERACIONES / SISTEMA con separadores y etiquetas en mayúsculas.

2. **Ítem activo** — se clarificó que el sub-ítem activo ("Mapa") debe tener un indicador de borde izquierdo azul (`::before { background: #004ac6 }`), no solo un color de fondo diferente.

3. **Tarjetas de bus** — el primer diseño usaba filas planas (estilo tabla). Se cambió a cards con borde redondeado, shadow sutil y un grid interior para salida/llegada.

4. **Popup del mapa** — el mockup original tenía un popup genérico de Leaflet. Se diseñó uno personalizado con cabecera azul oscura, campo de estado con badge de color, sección de última ubicación GPS y botones de acción en el footer.

## Sistema de diseño resultante — "LiveTrack Narrative"

Del proceso Stitch + iteración emergió un sistema visual consistente que se aplicó a todos los componentes:

### Paleta de colores

| Token | Valor | Uso |
|---|---|---|
| `--primary` | `#004ac6` | Acciones principales, marcadores activos, bordes de selección |
| `--nav-bg` | `#213145` | Sidebar (navy oscuro) |
| `--canvas-bg` | `#f4f7fb` | Fondo del workspace |
| `--on-surface` | `#0b1c30` | Texto principal |
| `--on-surface-variant` | `#697584` | Texto secundario |
| `--outline-variant` | `#e5eaef` | Bordes, separadores |
| Error/Destructivo | `#ba1a1a` | Estado de error |
| Éxito | `#166534` sobre `#dcfce7` | Badge "En ruta" |

### Tipografía

- **Inter** (Google Fonts) para toda la UI — clean, neutral, alta legibilidad en monitores de control.
- **Material Symbols Outlined** para iconografía — coherente, sin SVGs inline que mantener.

### Espaciado y radio

- Tarjetas: `border-radius: 12px`, `padding: 14px`
- Inputs: `border-radius: 10px`, `height: 38-42px`
- Badges: `border-radius: 4px` (cuadrado), `border-radius: 99px` (pill para chips)
- Transiciones: `0.12-0.15s ease` — perceptibles pero no lentas

### Variables CSS globales

```css
:root {
  --primary:                #004ac6;
  --nav-bg:                 #213145;
  --canvas-bg:              #f4f7fb;
  --panel-bg:               #fafbfc;
  --on-surface:             #0b1c30;
  --on-surface-variant:     #697584;
  --outline:                #8a9ab0;
  --outline-variant:        #e5eaef;
  --surface:                #fff;
  --surface-container:      #f0f3f7;
  --surface-container-high: #e5e9ef;
  --surface-container-low:  #f7f9fb;
  --error:                  #ba1a1a;
  --panel-w:                300px;
  --card-shadow:            0 1px 4px rgba(0,0,0,0.06);
}
```
