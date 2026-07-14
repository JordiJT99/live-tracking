<script setup lang="ts">
import { ref, computed } from 'vue'
import { useServicesStore } from '../stores/services'
import { useTrackingStore } from '../stores/tracking'
import ServiceListItem from './ServiceListItem.vue'

const services = useServicesStore()
const tracking = useTrackingStore()

const search = ref('')
const filter = ref<'all' | 'active' | 'idle'>('all')

const BUS_MODELS = [
  'Iveco Urbanway 12', 'Mercedes Citaro G', 'Solaris Urbino 18',
  "MAN Lion's City", 'Volvo 7900 Hybrid', 'CAF Urbos 3',
  'Alstom Aptis', 'Stadler TINA',
]

const filtered = computed(() => {
  let list = [...services.services].sort((a, b) => a.id - b.id)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(s =>
      s.name.toLowerCase().includes(q) ||
      `bus-${String(s.id).padStart(3, '0')}`.includes(q) ||
      (BUS_MODELS[(s.id - 1) % BUS_MODELS.length] ?? '').toLowerCase().includes(q)
    )
  }
  if (filter.value === 'active') list = list.filter(s => tracking.positions.has(s.id))
  if (filter.value === 'idle')   list = list.filter(s => !tracking.positions.has(s.id))
  return list
})

function select(id: number) {
  services.selectService(services.selectedId === id ? null : id)
}

function timeAgo(iso: string) {
  const diff = Date.now() - new Date(iso).getTime()
  if (diff < 60_000) return `Hace ${Math.floor(diff / 1000)}s`
  return `Hace ${Math.floor(diff / 60_000)} min`
}

const lastRefresh = computed(() =>
  tracking.lastRefresh ? timeAgo(tracking.lastRefresh.toISOString()) : '—'
)
</script>

<template>
  <aside class="panel">
    <!-- Header -->
    <div class="panel-header">
      <h3 class="panel-title">Servicios activos</h3>

      <!-- Search -->
      <div class="search-wrap">
        <span class="material-symbols-outlined search-icon">search</span>
        <input
          v-model="search"
          class="search-input"
          placeholder="Buscar línea o ruta..."
          type="text"
        />
      </div>

      <!-- Filter chips -->
      <div class="chips">
        <button class="chip" :class="{ 'chip--active': filter === 'all' }" @click="filter = 'all'">Todos</button>
        <button class="chip" :class="{ 'chip--active': filter === 'active' }" @click="filter = 'active'">Activos</button>
        <button class="chip" :class="{ 'chip--active': filter === 'idle' }" @click="filter = 'idle'">Inactivos</button>
      </div>
    </div>

    <!-- List -->
    <div class="panel-list">
      <div v-if="filtered.length === 0" class="empty">
        <div class="empty-icon">🛣️</div>
        <p v-if="services.services.length === 0">No hay servicios.<br/>Usa el simulador para generar.</p>
        <p v-else>Sin resultados para "{{ search }}".</p>
      </div>

      <ServiceListItem
        v-for="svc in filtered"
        :key="svc.id"
        :service="svc"
        :active="services.selectedId === svc.id"
        @select="select"
      />
    </div>

    <!-- Footer -->
    <div class="panel-footer">
      <span class="footer-refresh">
        <span class="material-symbols-outlined" style="font-size:14px">sync</span>
        {{ lastRefresh }}
      </span>
      <span>Auto-refresh 20s</span>
    </div>
  </aside>
</template>

<style scoped>
.panel {
  width: var(--panel-w);
  flex-shrink: 0;
  background: var(--panel-bg);
  border-right: 1px solid var(--outline-variant);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Header */
.panel-header {
  padding: 20px 20px 12px;
  border-bottom: 1px solid var(--outline-variant);
  background: #fff;
}
.panel-title {
  font-size: 18px; font-weight: 700; color: var(--on-surface);
  margin-bottom: 14px; letter-spacing: -0.01em;
}

/* Search */
.search-wrap { position: relative; margin-bottom: 12px; }
.search-icon {
  position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
  color: var(--outline); font-size: 20px; pointer-events: none;
}
.search-input {
  width: 100%; height: 38px;
  padding: 0 12px 0 36px;
  border: 1px solid var(--outline-variant); border-radius: 10px;
  font-size: 13px; color: var(--on-surface); background: var(--surface);
  outline: none; transition: border-color 0.15s, box-shadow 0.15s;
}
.search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(0,74,198,0.12); }

/* Chips */
.chips { display: flex; gap: 8px; }
.chip {
  padding: 5px 14px; border-radius: 99px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--outline-variant);
  background: var(--surface-container); color: var(--on-surface-variant);
  cursor: pointer; transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.chip:hover { background: var(--surface-container-high); color: var(--on-surface); }
.chip--active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* List */
.panel-list {
  flex: 1; overflow-y: auto;
  padding: 12px 12px 8px;
}
.panel-list::-webkit-scrollbar { width: 4px; }
.panel-list::-webkit-scrollbar-thumb { background: var(--outline-variant); border-radius: 2px; }

/* Footer */
.panel-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 20px; font-size: 11px; font-weight: 500; color: var(--outline);
  background: var(--surface-container-low); border-top: 1px solid var(--outline-variant);
}
.footer-refresh { display: flex; align-items: center; gap: 4px; }
</style>
