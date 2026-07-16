import { encode } from '../utils/polyline'
import { BARCELONA_ROUTES } from './routes'

export interface Service {
  id: number
  name: string
  start_time: string
  end_time: string
  polyline: string
}

export interface TrackingPoint {
  service_id: number
  latitude: number
  longitude: number
  created_at: string
}

export const ROUTE_CATALOG_SIZE = BARCELONA_ROUTES.length

// Starts empty — user generates services via the simulator
export const MOCK_SERVICES: Service[] = []
export const mockProgress: Record<number, number> = {}
// Last position emitted per service. The 20 s poll re-reads this instead of
// recomputing, so polling is idempotent: only a simulation tick moves a bus.
// Otherwise the poll (no GPS noise) and the tick (with noise) would each nudge
// the marker, making it hop twice per cycle instead of once every 20 s.
const lastPositions: Record<number, TrackingPoint> = {}

export function latestMockPositions(): TrackingPoint[] {
  return MOCK_SERVICES.map((s) => {
    const cached = lastPositions[s.id]
    if (cached) return cached
    const coords = BARCELONA_ROUTES[(s.id - 1) % BARCELONA_ROUTES.length]!.coords
    const pos = coords[(mockProgress[s.id] ?? 0) % coords.length]!
    const pt = { service_id: s.id, latitude: pos[0], longitude: pos[1], created_at: new Date().toISOString() }
    lastPositions[s.id] = pt
    return pt
  })
}

export function advanceMockSimulation(): TrackingPoint[] {
  const noise = () => (Math.random() - 0.5) * 0.0002
  return MOCK_SERVICES.map((s) => {
    const coords = BARCELONA_ROUTES[(s.id - 1) % BARCELONA_ROUTES.length]!.coords
    // loop route so buses keep moving; advance 4 steps per 20 s tick so each
    // tick is a visible hop (kept under the ~800 m snap threshold in LiveMap).
    mockProgress[s.id] = ((mockProgress[s.id] ?? 0) + 4) % coords.length
    const pos = coords[mockProgress[s.id]!]!
    const pt = { service_id: s.id, latitude: pos[0] + noise(), longitude: pos[1] + noise(), created_at: new Date().toISOString() }
    lastPositions[s.id] = pt
    return pt
  })
}

export function generateMockServices(count: number): Service[] {
  // Services are added in route order — MOCK_SERVICES.length is the next unused route index
  // simple pointer into route catalog; no route ever assigned twice
  const startIdx = MOCK_SERVICES.length
  const routes = BARCELONA_ROUTES.slice(startIdx, startIdx + count)

  const generated: Service[] = []
  for (let i = 0; i < routes.length; i++) {
    const routeIdx = startIdx + i
    const id = startIdx + i + 1
    const startH = 6 + (routeIdx * 2) % 12
    const svc: Service = {
      id,
      name: `Línea ${id} – ${routes[i]!.name}`,
      start_time: `2025-01-13T${String(startH).padStart(2, '0')}:00:00`,
      end_time:   `2025-01-13T${String(Math.min(startH + 9, 23)).padStart(2, '0')}:30:00`,
      polyline: encode(routes[i]!.coords),
    }
    MOCK_SERVICES.push(svc)
    mockProgress[id] = 0
    generated.push(svc)
  }
  return generated
}
