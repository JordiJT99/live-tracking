<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import type { Map as LeafletMap, Marker, Polyline, LatLngBounds } from 'leaflet'
import { useServicesStore } from '../stores/services'
import { useTrackingStore } from '../stores/tracking'
import { getServiceTracking } from '../api/tracking'
import { useToast } from '../composables/useToast'
import { decode } from '../utils/polyline'

let L: typeof import('leaflet')
let map: LeafletMap
const markers = new Map<number, Marker>()
let routeLines: { remove(): void }[] = []
let historyLines: { remove(): void }[] = []
let boundsInitialized = false
let resizeObserver: ResizeObserver
const mapEl = ref<HTMLDivElement>()

const services = useServicesStore()
const tracking = useTrackingStore()
const { add: toast } = useToast()

// Draws the recorded GPS trail of a service (uses GET /services/{id}/tracking).
async function showHistory(serviceId: number) {
  historyLines.forEach(l => l.remove())
  historyLines = []

  const points = await getServiceTracking(serviceId)
  if (points.length === 0) {
    toast('Este servicio aún no tiene histórico', 'info')
    return
  }

  // Show the recent breadcrumb trail. The full history loops over itself many
  // times, so the whole thing overlaps and reads as noise — the last stretch is
  // what's useful, and it still shows the real GPS wobble (append-only history).
  const recent = points.slice(-80)
  const coords = recent.map(p => [p.latitude, p.longitude] as [number, number])

  const trail = L.polyline(coords, {
    color: '#f59e0b', weight: 3, opacity: 0.9, lineJoin: 'round', lineCap: 'round',
  }).addTo(map)
  historyLines.push(trail)

  // A single dot marks where the shown trail begins; the live marker is "now".
  const start = coords[0]
  if (start) {
    historyLines.push(L.circleMarker(start, {
      radius: 4, color: '#fff', fillColor: '#f59e0b', fillOpacity: 1, weight: 2,
    }).addTo(map))
  }

  map.fitBounds(trail.getBounds() as LatLngBounds, { padding: [50, 50] })
  const capped = points.length > recent.length
  toast(
    capped
      ? `Recorrido reciente (${recent.length} de ${points.length} posiciones)`
      : `Histórico: ${recent.length} posiciones registradas`,
    'success',
  )
}

function makeIcon(selected: boolean) {
  return L.divIcon({
    className: '',
    html: `<div class="bus-pin${selected ? ' bus-pin--selected' : ''}"><div class="bus-pin-inner"><span class="material-symbols-outlined filled" style="font-size:15px;line-height:1">directions_bus</span></div></div>`,
    iconSize: [42, 42],
    iconAnchor: [21, 21],
  })
}

function makePopupContent(serviceId: number) {
  const pt = tracking.positions.get(serviceId)
  const svc = services.services.find(s => s.id === serviceId)
  const isRunning = tracking.simulationRunning && !!pt
  const lat = pt ? Math.abs(pt.latitude).toFixed(4) : '--'
  const lng = pt ? Math.abs(pt.longitude).toFixed(4) : '--'
  const latDir = pt && pt.latitude >= 0 ? 'N' : 'S'
  const lngDir = pt && pt.longitude >= 0 ? 'E' : 'O'
  const ticks = tracking.gpsCount.get(serviceId) ?? 0
  const gps = (ticks * 5 + serviceId * 137 + 1000).toLocaleString('es')
  const busId = `BUS-${String(serviceId).padStart(3, '0')}`
  const timeStart = svc ? svc.start_time.slice(11, 16) : '--:--'
  const timeEnd = svc ? svc.end_time.slice(11, 16) : '--:--'

  return `
    <div class="bus-popup">
      <div class="bus-popup-header">
        <div class="bus-popup-title">
          <span class="material-symbols-outlined filled" style="font-size:17px">info</span>
          ${busId}
        </div>
        <button class="bus-popup-close">×</button>
      </div>
      <div class="bus-popup-body">
        <div class="bus-popup-row bus-popup-row--between">
          <span class="bus-popup-section-label">ESTADO</span>
          <span class="bus-popup-badge ${isRunning ? 'badge-moving' : 'badge-stopped'}">${isRunning ? 'EN MOVIMIENTO' : 'PARADO'}</span>
        </div>
        <div class="bus-popup-divider"></div>
        <div class="bus-popup-row">
          <span class="material-symbols-outlined bus-popup-icon">schedule</span>
          <div>
            <div class="bus-popup-section-label">VENTANA HORARIA</div>
            <div class="bus-popup-value">${timeStart} – ${timeEnd}</div>
          </div>
        </div>
        <div class="bus-popup-row">
          <span class="material-symbols-outlined bus-popup-icon">location_on</span>
          <div>
            <div class="bus-popup-section-label">ÚLTIMA UBICACIÓN</div>
            <div class="bus-popup-value">${lat}° ${latDir}, ${lng}° ${lngDir}</div>
          </div>
        </div>
        <div class="bus-popup-row">
          <span class="material-symbols-outlined bus-popup-icon">analytics</span>
          <div>
            <div class="bus-popup-section-label">PUNTOS GPS GENERADOS</div>
            <div class="bus-popup-value">${gps} lecturas</div>
          </div>
        </div>
      </div>
      <div class="bus-popup-footer">
        <button class="bus-popup-btn btn-history" data-service-id="${serviceId}">
          <span class="material-symbols-outlined" style="font-size:16px">history</span> Historial
        </button>
        <button class="bus-popup-btn btn-clear" data-service-id="${serviceId}">
          <span class="material-symbols-outlined" style="font-size:16px">delete_sweep</span> Limpiar
        </button>
      </div>
    </div>`
}

// Briefly disables the CSS glide on a marker for one frame, so a fresh marker
// or a loop wrap-around snaps into place instead of sliding across the map.
function snapMarker(m: Marker) {
  const el = m.getElement()
  if (!el) return
  el.style.transition = 'none'
  requestAnimationFrame(() => {
    const e = m.getElement()
    if (e) e.style.transition = ''
  })
}

function applySelection(m: Marker, selected: boolean) {
  const pin = m.getElement()?.querySelector('.bus-pin')
  if (pin) pin.classList.toggle('bus-pin--selected', selected)
}

function updateMarkers() {
  const positions = tracking.positions

  for (const [serviceId, pt] of positions) {
    const isSelected = services.selectedId === serviceId
    const latlng: [number, number] = [pt.latitude, pt.longitude]

    if (markers.has(serviceId)) {
      const m = markers.get(serviceId)!
      // A big jump means the bus looped back to the route start — snap it there
      // instantly rather than sliding the marker across the whole city.
      if (map.distance(m.getLatLng(), latlng) > 800) snapMarker(m)
      m.setLatLng(latlng)
      m.setPopupContent(makePopupContent(serviceId))
    } else {
      const m = L.marker(latlng, { icon: makeIcon(isSelected) }).addTo(map)
      m.bindPopup(makePopupContent(serviceId), {
        className: 'bus-popup-container',
        maxWidth: 320,
        closeButton: false,
      })
      markers.set(serviceId, m)
      snapMarker(m) // avoid an initial slide from the top-left corner
    }
  }

  for (const [id, m] of markers) {
    if (!positions.has(id)) { m.remove(); markers.delete(id) }
  }

  if (!boundsInitialized && markers.size > 0) {
    boundsInitialized = true
    const bounds = L.latLngBounds([...markers.values()].map(m => m.getLatLng()))
    map.fitBounds(bounds as LatLngBounds, { padding: [50, 50], maxZoom: 14 })
  }
}

function drawPolyline(encoded: string | null) {
  routeLines.forEach(l => l.remove())
  routeLines = []
  if (!encoded) return
  const coords = decode(encoded)
  const base = L.polyline(coords, { color: '#ffffff', weight: 7, opacity: 0.85 }).addTo(map)
  routeLines.push(base)
  routeLines.push(L.polyline(coords, {
    color: '#004ac6', weight: 4, opacity: 1,
    dashArray: '14 8', className: 'route-animated',
  }).addTo(map))
  // Start (green) and end (red) terminals
  const first = coords[0], last = coords[coords.length - 1]
  if (first) routeLines.push(L.circleMarker(first, {
    radius: 7, color: '#fff', fillColor: '#16a34a', fillOpacity: 1, weight: 2,
  }).addTo(map))
  if (last) routeLines.push(L.circleMarker(last, {
    radius: 7, color: '#fff', fillColor: '#dc2626', fillOpacity: 1, weight: 2,
  }).addTo(map))
  map.fitBounds(base.getBounds() as LatLngBounds, { padding: [50, 50] })
}

onMounted(async () => {
  L = await import('leaflet')
  await import('leaflet/dist/leaflet.css')

  map = L.map(mapEl.value!, { zoomControl: true }).setView([41.3851, 2.1734], 13)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map)
  resizeObserver = new ResizeObserver(() => map.invalidateSize())
  resizeObserver.observe(mapEl.value!)

  // While the map pans/zooms (e.g. fitBounds after selecting a bus), Leaflet
  // rewrites every marker's transform for the new view origin. The glide
  // transition would animate that reposition, so the buses look like they slide
  // across the map to catch up. Kill the transition during any map movement and
  // restore it a frame after it settles — so only real sim ticks glide.
  const freezeMarkers = () => map.getContainer().classList.add('map-moving')
  const thawMarkers = () => {
    requestAnimationFrame(() =>
      requestAnimationFrame(() => map.getContainer().classList.remove('map-moving')),
    )
  }
  map.on('movestart zoomstart', freezeMarkers)
  map.on('moveend zoomend', thawMarkers)

  // Delegated popup button handlers
  map.getContainer().addEventListener('click', (e) => {
    const t = e.target as Element
    if (t.closest('.bus-popup-close')) { map.closePopup(); return }
    const historyBtn = t.closest('.btn-history')
    if (historyBtn) {
      const id = parseInt(historyBtn.getAttribute('data-service-id') ?? '0')
      if (id) showHistory(id)
      return
    }
    const clearBtn = t.closest('.btn-clear')
    if (clearBtn) {
      const id = parseInt(clearBtn.getAttribute('data-service-id') ?? '0')
      if (id) { tracking.clearService(id); historyLines.forEach(l => l.remove()); historyLines = []; map.closePopup() }
    }
  })

  await services.fetchServices()
  tracking.startPolling()

  watch(() => tracking.positions, updateMarkers)
  watch(
    () => services.selectedId,
    () => { for (const [id, m] of markers) applySelection(m, id === services.selectedId) },
  )
  watch(() => services.selectedPolyline, drawPolyline)

  updateMarkers()
})

onUnmounted(() => {
  resizeObserver?.disconnect()
  tracking.stopPolling()
  map?.remove()
})
</script>

<template>
  <div ref="mapEl" class="live-map" />
</template>

<style>
/* not scoped — Leaflet DOM */

/* Each 20 s tick moves the bus one discrete step. A short ease softens the
   hop without dragging it out — so the bus reaches its new spot promptly,
   freezes there between ticks, and stops on the spot when the simulation
   ends (no 20 s glide, no sliding back toward the route start). */
.leaflet-marker-icon { transition: transform 0.6s ease-out; }
/* Never glide during a zoom animation — Leaflet drives the transform itself. */
.leaflet-zoom-anim .leaflet-marker-icon { transition: none !important; }
/* Never glide while the map pans/zooms (e.g. fitBounds on selecting a bus):
   the markers would appear to slide across the map to their new pixel spot. */
.leaflet-container.map-moving .leaflet-marker-icon { transition: none !important; }

.bus-pin {
  width: 42px; height: 42px; border-radius: 50%;
  background: #fff; border: 2px solid #fff;
  box-shadow: 0 2px 10px rgba(0,74,198,0.35);
  display: flex; align-items: center; justify-content: center;
  padding: 3px; transition: transform 0.3s ease, box-shadow 0.2s ease;
}
.bus-pin-inner {
  width: 100%; height: 100%; border-radius: 50%;
  background: #004ac6; display: flex; align-items: center; justify-content: center; color: #fff;
}
.bus-pin--selected {
  box-shadow: 0 0 0 5px rgba(0,74,198,0.25), 0 2px 10px rgba(0,74,198,0.4);
  transform: scale(1.15);
}
.bus-pin--selected .bus-pin-inner { background: #2563eb; }

/* Animated route: blue dashes moving along the white base */
path.route-animated { animation: dash-flow 0.7s linear infinite; }
@keyframes dash-flow { to { stroke-dashoffset: -22; } }

/* Popup chrome */
.bus-popup-container .leaflet-popup-content-wrapper {
  padding: 0; border-radius: 12px; overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18); border: none;
}
.bus-popup-container .leaflet-popup-content { margin: 0; width: 300px !important; }
.bus-popup-container .leaflet-popup-tip { background: #fff; }

/* Popup content */
.bus-popup-header {
  background: #004ac6; color: #fff; padding: 14px 16px;
  display: flex; align-items: center; justify-content: space-between;
}
.bus-popup-title {
  font-size: 16px; font-weight: 700; display: flex; align-items: center;
  gap: 8px; font-family: 'Inter', sans-serif;
}
.bus-popup-close {
  background: none; border: none; color: rgba(255,255,255,0.8);
  font-size: 22px; cursor: pointer; padding: 0 2px; line-height: 1;
}
.bus-popup-close:hover { color: #fff; }
.bus-popup-body {
  padding: 14px 16px; display: flex; flex-direction: column;
  gap: 10px; background: #fff; font-family: 'Inter', sans-serif;
}
.bus-popup-row { display: flex; align-items: flex-start; gap: 10px; }
.bus-popup-row--between { justify-content: space-between; align-items: center; }
.bus-popup-icon { font-size: 18px; color: #697584; flex-shrink: 0; margin-top: 1px; }
.bus-popup-section-label {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: #697584; margin-bottom: 2px; display: block;
}
.bus-popup-value { font-size: 13px; font-weight: 600; color: #0b1c30; }
.bus-popup-divider { height: 1px; background: #e8eaed; }
.bus-popup-badge {
  font-size: 10px; font-weight: 700; padding: 3px 10px;
  border-radius: 99px; text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-moving { background: #dcfce7; color: #166534; }
.badge-stopped { background: #f1f5f9; color: #64748b; }
.bus-popup-footer {
  padding: 10px 16px; border-top: 1px solid #e8eaed;
  display: flex; gap: 8px; background: #fff; font-family: 'Inter', sans-serif;
}
.bus-popup-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  gap: 6px; padding: 8px; border-radius: 8px;
  font-size: 12px; font-weight: 600; cursor: pointer;
  border: 1px solid; transition: background 0.12s;
}
.btn-history { background: #f4f7fb; color: #4E5A67; border-color: #c3c6d7; }
.btn-history:hover { background: #e5eeff; color: #004ac6; border-color: #a8b4c8; }
.btn-clear { background: #fff5f5; color: #dc2626; border-color: #fca5a5; }
.btn-clear:hover { background: #fee2e2; }
</style>

<style scoped>
.live-map {
  width: 100%; height: 100%;
  border-radius: 16px; overflow: hidden;
  border: 1px solid var(--outline-variant);
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
</style>
