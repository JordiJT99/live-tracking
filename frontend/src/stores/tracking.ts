import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getLatestPositions } from '../api/tracking'
import { startSimulation as apiStartSimulation, stopSimulation as apiStopSimulation, getSimulationStatus } from '../api/simulation'
import { USE_MOCK } from '../api/config'
import { advanceMockSimulation, type TrackingPoint } from '../fixtures'

// Map refresh cadence required by the spec (every 20-30 s). The simulator ticks
// faster (5 s), so each poll advances the bus a few points at once.
// The mock runs on the same 20 s cadence so its movement matches real mode:
// one discrete step per tick, no sub-second crawling.
const POLL_INTERVAL_MS = 20_000
const MOCK_TICK_MS = 20_000

export { type TrackingPoint }

export const useTrackingStore = defineStore('tracking', () => {
  const positions = ref<Map<number, TrackingPoint>>(new Map())
  const gpsCount = ref<Map<number, number>>(new Map())
  const simulationRunning = ref(false)
  const lastRefresh = ref<Date | null>(null)

  let pollTimer: ReturnType<typeof setInterval> | null = null
  let mockTimer: ReturnType<typeof setInterval> | null = null
  const clearedIds = new Set<number>()

  async function refreshPositions() {
    const pts = await getLatestPositions()
    const next = new Map(positions.value)
    const gc = new Map(gpsCount.value)
    for (const p of pts) {
      if (clearedIds.has(p.service_id)) continue
      next.set(p.service_id, p)
      gc.set(p.service_id, (gc.get(p.service_id) ?? 0) + 1)
    }
    positions.value = next
    gpsCount.value = gc
    lastRefresh.value = new Date()
  }

  function startPolling() {
    reconcileRunning() // adopt the simulator's real state (e.g. after a page reload)
    refreshPositions()
    pollTimer = setInterval(refreshPositions, POLL_INTERVAL_MS)
  }

  // Keeps the UI honest: the running flag reflects the microservice, not just
  // the last button click in this tab.
  async function reconcileRunning() {
    if (USE_MOCK) return
    try {
      const status = await getSimulationStatus()
      simulationRunning.value = status.running
    } catch { /* leave the flag as-is if status is unreachable */ }
  }

  function stopPolling() {
    if (pollTimer !== null) { clearInterval(pollTimer); pollTimer = null }
  }

  function applyMockTick() {
    const pts = advanceMockSimulation()
    const next = new Map(positions.value)
    const gc = new Map(gpsCount.value)
    for (const p of pts) {
      next.set(p.service_id, p)
      gc.set(p.service_id, (gc.get(p.service_id) ?? 0) + 1)
    }
    positions.value = next
    gpsCount.value = gc
    lastRefresh.value = new Date()
  }

  async function startSimulation() {
    simulationRunning.value = true
    if (USE_MOCK) {
      // First movement lands on the first 20 s tick, not on the click, so the
      // fleet holds at its starting positions for one full refresh interval.
      mockTimer = setInterval(applyMockTick, MOCK_TICK_MS)
    } else {
      // Tell the real microservice to begin ticking, then sample immediately.
      await apiStartSimulation()
      await refreshPositions()
    }
  }

  async function stopSimulation() {
    simulationRunning.value = false
    if (USE_MOCK) {
      if (mockTimer !== null) { clearInterval(mockTimer); mockTimer = null }
    } else {
      await apiStopSimulation()
    }
  }

  function clearService(id: number) {
    clearedIds.add(id)
    const next = new Map(positions.value); next.delete(id); positions.value = next
    const gc = new Map(gpsCount.value); gc.delete(id); gpsCount.value = gc
  }

  return {
    positions, gpsCount, simulationRunning, lastRefresh,
    refreshPositions, startPolling, stopPolling, startSimulation, stopSimulation, clearService,
  }
})
