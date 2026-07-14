import { USE_MOCK } from './config'
import http from './http'

export type SimulationStatus = { running: boolean }

export async function startSimulation(): Promise<void> {
  if (USE_MOCK) return
  await http.post('/simulation/start')
}

export async function stopSimulation(): Promise<void> {
  if (USE_MOCK) return
  await http.post('/simulation/stop')
}

export async function getSimulationStatus(): Promise<SimulationStatus> {
  if (USE_MOCK) return { running: false } // managed locally in the store
  const { data } = await http.get('/simulation/status')
  return data
}
