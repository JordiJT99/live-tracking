<script setup lang="ts">
import { ref, computed } from 'vue'
import { useServicesStore } from '../stores/services'
import { useTrackingStore } from '../stores/tracking'
import { useToast } from '../composables/useToast'
import { ROUTE_CATALOG_SIZE } from '../fixtures'

const services = useServicesStore()
const tracking = useTrackingStore()
const { add: toast } = useToast()
const count = ref(5)
const generating = ref(false)

const routesLeft = computed(() => ROUTE_CATALOG_SIZE - services.services.length)
const catalogFull = computed(() => routesLeft.value <= 0)

const BUS_MODELS = [
  'Iveco Urbanway 12', 'Mercedes Citaro G', 'Solaris Urbino 18',
  "MAN Lion's City", 'Volvo 7900 Hybrid', 'CAF Urbos 3',
  'Alstom Aptis', 'Stadler TINA',
]
const busName = (id: number) =>
  `BUS-${String(id).padStart(3, '0')} — ${BUS_MODELS[(id - 1) % BUS_MODELS.length]}`

async function generate() {
  if (count.value < 1 || count.value > 50) return
  if (catalogFull.value) {
    toast(`Catálogo completo — máximo ${ROUTE_CATALOG_SIZE} servicios`, 'error')
    return
  }
  generating.value = true
  try {
    const created = await services.generate(count.value)
    if (created.length === 0) {
      toast(`No quedan rutas disponibles (máximo ${ROUTE_CATALOG_SIZE})`, 'error')
    } else {
      await tracking.refreshPositions()
      for (const svc of created) {
        toast(`Se ha generado el servicio del bus ${busName(svc.id)}`, 'success')
      }
    }
  } catch {
    toast('Error al generar servicios', 'error')
  } finally {
    generating.value = false
  }
}

async function toggleSimulation() {
  if (tracking.simulationRunning) {
    await tracking.stopSimulation()
    toast('Simulación detenida', 'info')
  } else {
    if (services.services.length === 0) {
      toast('Genera servicios primero', 'error')
      return
    }
    await tracking.startSimulation()
    toast('Simulación iniciada', 'success')
  }
}
</script>

<template>
  <div class="sim-bar">
    <!-- Sim status badge -->
    <div v-if="tracking.simulationRunning" class="sim-status">
      <span class="sim-dot" />
      <span class="sim-status-text">Simulación activa</span>
      <button class="sim-stop-link" @click="toggleSimulation">Detener</button>
    </div>

    <!-- Generate input + button -->
    <div class="sim-generate">
      <input
        v-model.number="count"
        type="number" min="1" max="50"
        class="sim-count-input"
        :disabled="catalogFull"
        title="Número de servicios a crear"
      />
      <button
        class="btn btn-ghost"
        :disabled="generating || count < 1 || catalogFull"
        @click="generate"
      >
        <span v-if="generating" class="spinner" style="border-top-color: var(--primary); border-color: var(--outline-variant)" />
        <span v-else class="material-symbols-outlined" style="font-size:18px">add</span>
        Crear servicios
      </button>
      <span v-if="catalogFull" class="catalog-full-msg">
        <span class="material-symbols-outlined" style="font-size:14px">info</span>
        Catálogo completo
      </span>
      <span v-else class="routes-left">{{ routesLeft }} rutas disponibles</span>
    </div>

    <!-- Start simulation -->
    <button
      v-if="!tracking.simulationRunning"
      class="btn btn-primary"
      @click="toggleSimulation"
    >
      <span class="material-symbols-outlined" style="font-size:18px">play_arrow</span>
      Iniciar simulación
    </button>
  </div>
</template>

<style scoped>
.sim-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}

.sim-status {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 99px;
}
.sim-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #22c55e;
  animation: pulse-dot 1.8s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:0.6; transform:scale(1.3); }
}
.sim-status-text { font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.05em; }
.sim-stop-link { font-size: 11px; font-weight: 700; color: var(--error); background: none; border: none; cursor: pointer; padding: 0; }
.sim-stop-link:hover { text-decoration: underline; }

.sim-generate { display: flex; align-items: center; gap: 6px; }
.sim-count-input {
  width: 56px; height: 36px;
  border: 1px solid var(--outline-variant); border-radius: 8px;
  padding: 0 8px; font-size: 13px;
  text-align: center; outline: none;
  color: var(--on-surface);
  transition: border-color 0.15s;
}
.sim-count-input:focus { border-color: var(--primary); }
.sim-count-input:disabled { opacity: 0.4; cursor: not-allowed; }

.routes-left {
  font-size: 11px;
  color: var(--outline);
  white-space: nowrap;
}
.catalog-full-msg {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  color: var(--error);
  white-space: nowrap;
}
</style>
