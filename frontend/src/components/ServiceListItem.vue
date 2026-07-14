<script setup lang="ts">
import type { Service } from '../stores/services'
import { useTrackingStore } from '../stores/tracking'

const props = defineProps<{ service: Service; active: boolean }>()
defineEmits<{ select: [id: number] }>()

const tracking = useTrackingStore()

const BUS_MODELS = [
  'Iveco Urbanway 12', 'Mercedes Citaro G', 'Solaris Urbino 18',
  "MAN Lion's City", 'Volvo 7900 Hybrid', 'CAF Urbos 3',
  'Alstom Aptis', 'Stadler TINA',
]

function busId(id: number) { return `BUS-${String(id).padStart(3, '0')}` }
function busModel(id: number) { return BUS_MODELS[(id - 1) % BUS_MODELS.length] }
function fmt(iso: string) { return iso.slice(11, 16) }
function isRunning(svc: Service) {
  return tracking.simulationRunning && tracking.positions.has(svc.id)
}
</script>

<template>
  <button
    class="card"
    :class="{ 'card--active': active }"
    :aria-pressed="active"
    @click="$emit('select', service.id)"
  >
    <div class="card-top">
      <div class="card-left">
        <div class="card-icon" :class="{ 'card-icon--active': active }">
          <span class="material-symbols-outlined filled">directions_bus</span>
        </div>
        <div>
          <div class="card-id">{{ busId(service.id) }}</div>
          <div class="card-model">{{ busModel(service.id) }}</div>
        </div>
      </div>
      <div class="card-top-right">
        <span v-if="active" class="card-check material-symbols-outlined filled">check_circle</span>
        <span class="badge" :class="isRunning(service) ? 'badge-active' : 'badge-idle'">
          {{ isRunning(service) ? 'En ruta' : 'Disponible' }}
        </span>
      </div>
    </div>

    <div class="card-grid">
      <div>
        <div class="card-grid-label">Salida</div>
        <div class="card-grid-val">{{ fmt(service.start_time) }}</div>
      </div>
      <div>
        <div class="card-grid-label">Llegada est.</div>
        <div class="card-grid-val">{{ fmt(service.end_time) }}</div>
      </div>
    </div>
  </button>
</template>

<style scoped>
.card {
  display: flex; flex-direction: column;
  width: 100%; margin-bottom: 10px;
  padding: 14px 14px 12px;
  background: #fff; border: 1px solid var(--outline-variant);
  border-radius: 12px; box-shadow: var(--card-shadow);
  cursor: pointer; text-align: left; position: relative;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
}
.card:hover { border-color: #a8b4c8; box-shadow: 0 4px 16px rgba(0,0,0,0.09); transform: translateY(-1px); }
.card.card--active { border: 2px solid var(--primary); background: rgba(0,74,198,0.04); box-shadow: 0 2px 12px rgba(0,74,198,0.15); }

.card-check { font-size: 16px; color: var(--primary); line-height: 1; }

.card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.card-top-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.card-left { display: flex; align-items: center; gap: 12px; }

.card-icon {
  width: 40px; height: 40px; border-radius: 10px;
  background: var(--surface-container);
  display: flex; align-items: center; justify-content: center;
  color: var(--primary); flex-shrink: 0;
}
.card-icon.card-icon--active { background: var(--primary); color: #fff; }
.card-icon .material-symbols-outlined { font-size: 22px; }

.card-id { font-size: 15px; font-weight: 700; color: var(--on-surface); line-height: 1.2; }
.card--active .card-id { color: var(--primary); }
.card-model { font-size: 11px; color: var(--outline); margin-top: 2px; }

.badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em; flex-shrink: 0; }
.badge-active { background: #dcfce7; color: #166534; }
.badge-idle   { background: var(--surface-container); color: var(--on-surface-variant); }

.card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 10px; border-top: 1px solid var(--outline-variant); }
.card--active .card-grid { border-top-color: rgba(0,74,198,0.15); }
.card-grid-label { font-size: 10px; color: var(--outline); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
.card-grid-val { font-size: 13px; font-weight: 600; color: var(--on-surface); }
.card--active .card-grid-label { color: rgba(0,74,198,0.6); }
</style>
