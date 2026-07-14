import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getLatestPositions } from '../api/tracking'
import { USE_MOCK } from '../api/config'
import { advanceMockSimulation, type TrackingPoint } from '../fixtures'

const POLL_INTERVAL_MS = 20_000
const MOCK_TICK_MS = 2_000

export { type TrackingPoint }

export const useTrackingStore = defineStore('tracking', () => {
  const positions = ref<Map<number, TrackingPoint>>(new Map())
  const gpsCount = ref<Map<number, number>>(new Map())
  const simulationRunning = ref(false)
  const lastRefresh = ref<Date | null>(null)

  let pollTimer: ReturnType<typeof setInterval> | null = null
  let mockTimer: ReturnType<typeof setInterval> | null = null

  async function refreshPositions() {
    const pts = await getLatestPositions()
    const next = new Map(positions.value)
    for (const p of pts) next.set(p.service_id, p)
    positions.value = next
    lastRefresh.value = new Date()
  }

  function startPolling() {
    refreshPositions()
    pollTimer = setInterval(refreshPositions, POLL_INTERVAL_MS)
  }

  function stopPolling() {
    if (pollTimer !== null) { clearInterval(pollTimer); pollTimer = null }
  }

  async function startSimulation() {
    simulationRunning.value = true
    if (USE_MOCK) {
      mockTimer = setInterval(() => {
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
      }, MOCK_TICK_MS)
    }
  }

  async function stopSimulation() {
    simulationRunning.value = false
    if (mockTimer !== null) { clearInterval(mockTimer); mockTimer = null }
  }

  function clearService(id: number) {
    const next = new Map(positions.value); next.delete(id); positions.value = next
    const gc = new Map(gpsCount.value); gc.delete(id); gpsCount.value = gc
  }

  return {
    positions, gpsCount, simulationRunning, lastRefresh,
    refreshPositions, startPolling, stopPolling, startSimulation, stopSimulation, clearService,
  }
})
