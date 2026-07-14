import { USE_MOCK } from './config'
import http from './http'
import { latestMockPositions, type TrackingPoint } from '../fixtures'

export async function getLatestPositions(): Promise<TrackingPoint[]> {
  if (USE_MOCK) return latestMockPositions()
  const { data } = await http.get('/tracking/latest')
  return data.data
}

export async function getServiceTracking(
  serviceId: number,
): Promise<TrackingPoint[]> {
  if (USE_MOCK) return []
  const { data } = await http.get(`/services/${serviceId}/tracking`)
  return data.data
}
