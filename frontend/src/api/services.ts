import { USE_MOCK } from './config'
import http from './http'
import {
  MOCK_SERVICES,
  generateMockServices,
  type Service,
} from '../fixtures'

export async function listServices(): Promise<Service[]> {
  if (USE_MOCK) return [...MOCK_SERVICES]
  const { data } = await http.get('/services')
  return data.data
}

export async function getService(id: number): Promise<Service> {
  if (USE_MOCK) {
    const svc = MOCK_SERVICES.find((s) => s.id === id)
    if (!svc) throw new Error(`Service ${id} not found`)
    return svc
  }
  const { data } = await http.get(`/services/${id}`)
  return data.data
}

export async function generateServices(count: number): Promise<Service[]> {
  if (USE_MOCK) return generateMockServices(count)
  const { data } = await http.post('/services/generate', { count })
  return data.data
}
