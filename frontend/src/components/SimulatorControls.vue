<script setup lang="ts">
import { ref } from 'vue'
import { useServicesStore } from '../stores/services'
import { useTrackingStore } from '../stores/tracking'
import { useToast } from '../composables/useToast'

const services = useServicesStore()
const tracking = useTrackingStore()
const { add: toast } = useToast()
const count = ref(5)
const generating = ref(false)

async function generate() {
  if (count.value < 1 || count.value > 50) return
  generating.value = true
  try {
    const created = await services.generate(count.value)
    // New services get an initial position from the simulator, so refresh markers.
    await tracking.refreshPositions()
    toast(`${created.length} servicio(s) generado(s)`, 'success')
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
        title="Número de servicios a crear (1-50 por lote)"
      />
      <button
        class="btn btn-ghost"
        :disabled="generating || count < 1"
        @click="generate"
      >
        <span v-if="generating" class="spinner" style="border-top-color: var(--primary); border-color: var(--outline-variant)" />
        <span v-else class="material-symbols-outlined" style="font-size:18px">add</span>
        Crear servicios
      </button>
      <span class="routes-left">Hasta 50 por lote</span>
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
