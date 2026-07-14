import { defineStore } from 'pinia'
import { ref } from 'vue'
import { listServices, getService, generateServices } from '../api/services'
import type { Service } from '../fixtures'

export { type Service }

export const useServicesStore = defineStore('services', () => {
  const services = ref<Service[]>([])
  const selectedId = ref<number | null>(null)
  const selectedPolyline = ref<string | null>(null)
  const loading = ref(false)

  async function fetchServices() {
    services.value = await listServices()
  }

  async function generate(count: number) {
    loading.value = true
    try {
      const created = await generateServices(count)
      services.value = [...services.value, ...created]
      return created
    } finally {
      loading.value = false
    }
  }

  async function selectService(id: number | null) {
    if (id === null) {
      selectedId.value = null
      selectedPolyline.value = null
      return
    }
    selectedId.value = id
    const svc = services.value.find((s) => s.id === id)
    if (svc?.polyline) {
      selectedPolyline.value = svc.polyline
    } else {
      const full = await getService(id)
      selectedPolyline.value = full.polyline
    }
  }

  return { services, selectedId, selectedPolyline, loading, fetchServices, generate, selectService }
})
