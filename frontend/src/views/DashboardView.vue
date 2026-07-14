<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import ServicePanel from '../components/ServicePanel.vue'
import LiveMap from '../components/LiveMap.vue'
import SimulatorControls from '../components/SimulatorControls.vue'

const auth = useAuthStore()
const router = useRouter()

const collapsed = ref(false)
const fleetExpanded = ref(true)

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="app-shell">
    <!-- Sidebar -->
    <nav class="sidebar" :class="{ 'sidebar--collapsed': collapsed }">

      <!-- Brand -->
      <div class="brand">
        <div class="brand-icon">
          <span class="material-symbols-outlined filled">directions_bus</span>
        </div>
        <template v-if="!collapsed">
          <div class="brand-text">
            <div class="brand-name">LiveTrack</div>
            <div class="brand-sub">Fleet Management</div>
          </div>
        </template>
      </div>

      <!-- Nav body -->
      <div class="nav-body">

        <!-- FLOTA -->
        <div v-if="!collapsed" class="section-label">FLOTA</div>

        <button class="nav-item nav-item--active" @click="fleetExpanded = !fleetExpanded">
          <span class="material-symbols-outlined filled">location_on</span>
          <span v-if="!collapsed" class="nav-label">Live Tracking</span>
          <span v-if="!collapsed" class="material-symbols-outlined nav-chevron">
            {{ fleetExpanded ? 'expand_less' : 'expand_more' }}
          </span>
        </button>

        <template v-if="fleetExpanded && !collapsed">
          <a class="nav-sub nav-sub--active">Mapa</a>
          <a class="nav-sub">Vehículos</a>
          <a class="nav-sub">Conductores</a>
          <a class="nav-sub">Rutas</a>
        </template>

        <!-- OPERACIONES -->
        <div v-if="!collapsed" class="section-label">OPERACIONES</div>

        <a class="nav-item">
          <span class="material-symbols-outlined">precision_manufacturing</span>
          <span v-if="!collapsed" class="nav-label">Simulador</span>
        </a>
        <a class="nav-item">
          <span class="material-symbols-outlined">analytics</span>
          <span v-if="!collapsed" class="nav-label">Informes</span>
        </a>

        <!-- SISTEMA -->
        <div v-if="!collapsed" class="section-label">SISTEMA</div>

        <a class="nav-item">
          <span class="material-symbols-outlined">settings</span>
          <span v-if="!collapsed" class="nav-label">Configuración</span>
        </a>
      </div>

      <!-- Bottom -->
      <div class="sidebar-bottom">
        <div class="bottom-divider" />

        <a class="nav-item">
          <span class="material-symbols-outlined">notifications</span>
          <span v-if="!collapsed" class="nav-label">Notificaciones</span>
          <span v-if="!collapsed" class="nav-badge">3</span>
        </a>
        <a class="nav-item">
          <span class="material-symbols-outlined">chat</span>
          <span v-if="!collapsed" class="nav-label">Soporte</span>
          <span v-if="!collapsed" class="nav-badge">2</span>
        </a>

        <!-- User profile -->
        <div class="user-row">
          <div class="user-avatar">D</div>
          <template v-if="!collapsed">
            <div class="user-info">
              <div class="user-name">Demo User</div>
              <div class="user-role">Operador</div>
            </div>
            <button class="logout-btn" @click="logout" title="Cerrar sesión">
              <span class="material-symbols-outlined">logout</span>
            </button>
          </template>
        </div>
      </div>
    </nav>

    <!-- Sidebar toggle (floating edge button) -->
    <button
      class="sidebar-toggle"
      :class="{ 'sidebar-toggle--collapsed': collapsed }"
      @click="collapsed = !collapsed"
      :title="collapsed ? 'Expandir' : 'Colapsar'"
    >
      <span class="material-symbols-outlined">{{ collapsed ? 'chevron_right' : 'chevron_left' }}</span>
    </button>

    <!-- Workspace -->
    <div class="workspace">
      <header class="topbar">
        <h2 class="topbar-title">Live Tracking</h2>
        <SimulatorControls />
      </header>

      <div class="content-area">
        <ServicePanel />
        <div class="map-wrap">
          <LiveMap />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.app-shell { position: relative; display: flex; height: 100vh; overflow: hidden; background: var(--canvas-bg); }

/* ── Sidebar ── */
.sidebar {
  width: 260px; flex-shrink: 0;
  background: var(--nav-bg);
  display: flex; flex-direction: column;
  transition: width 0.2s ease;
  overflow: hidden; z-index: 50;
}
.sidebar--collapsed { width: 60px; }

/* Brand */
.brand {
  display: flex; align-items: center; gap: 10px;
  padding: 20px 14px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.brand-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: var(--primary);
  display: flex; align-items: center; justify-content: center; color: #fff;
}
.brand-icon .material-symbols-outlined { font-size: 20px; }
.brand-text { flex: 1; min-width: 0; }
.brand-name { font-size: 16px; font-weight: 700; color: #fff; line-height: 1.2; white-space: nowrap; }
.brand-sub { font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(192,198,219,0.6); }

/* Sidebar toggle — floating circle on the sidebar/content boundary */
.sidebar-toggle {
  position: absolute;
  left: 247px; /* 260px sidebar - half button width (13px) */
  top: 36px;
  z-index: 60;
  width: 26px; height: 26px;
  background: #fff;
  border: 1px solid var(--outline-variant);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,0.12);
  transition: left 0.2s ease, background 0.12s;
}
.sidebar-toggle--collapsed { left: 47px; } /* 60px collapsed - 13px */
.sidebar-toggle:hover { background: #f0f4ff; }
.sidebar-toggle .material-symbols-outlined { font-size: 16px; color: var(--on-surface-variant); }

/* Nav body */
.nav-body { flex: 1; padding: 8px 0; overflow-y: auto; overflow-x: hidden; }
.nav-body::-webkit-scrollbar { width: 0; }

.section-label {
  font-size: 10px; font-weight: 700; letter-spacing: 0.1em;
  color: rgba(192,198,219,0.45); padding: 16px 16px 4px;
  white-space: nowrap;
}

.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; width: 100%;
  color: rgba(192,198,219,0.75); font-size: 13.5px; font-weight: 400;
  text-decoration: none; cursor: pointer;
  background: none; border: none; text-align: left;
  transition: background 0.1s, color 0.1s;
  white-space: nowrap;
}
.nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
.nav-item--active { color: #fff !important; font-weight: 500; }
.nav-item--active .material-symbols-outlined:first-child { color: var(--primary); }

.nav-label { flex: 1; }
.nav-chevron { font-size: 18px; color: rgba(192,198,219,0.5); }

/* Sub-items */
.nav-sub {
  display: flex; align-items: center;
  padding: 8px 14px 8px 48px;
  color: rgba(192,198,219,0.6); font-size: 13px;
  text-decoration: none; cursor: pointer;
  white-space: nowrap;
  transition: color 0.1s;
  position: relative;
}
.nav-sub:hover { color: #fff; }
.nav-sub--active {
  color: #fff; font-weight: 500;
}
.nav-sub--active::before {
  content: ''; position: absolute; left: 28px;
  top: 50%; transform: translateY(-50%);
  width: 3px; height: 16px;
  background: var(--primary); border-radius: 2px;
}

/* Bottom */
.sidebar-bottom { padding: 0 0 12px; }
.bottom-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 8px 0; }

.nav-badge {
  background: var(--primary); color: #fff;
  font-size: 10px; font-weight: 700;
  padding: 1px 6px; border-radius: 99px; min-width: 18px; text-align: center;
}

/* User row */
.user-row {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; margin-top: 4px;
}
.user-avatar {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--primary); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
}
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; }
.user-role { font-size: 11px; color: rgba(192,198,219,0.6); }
.logout-btn {
  background: none; border: none; cursor: pointer; padding: 4px;
  color: rgba(192,198,219,0.6); border-radius: 6px;
  display: flex; align-items: center;
  transition: color 0.12s;
}
.logout-btn:hover { color: #fff; }
.logout-btn .material-symbols-outlined { font-size: 18px; }

/* ── Workspace ── */
.workspace { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

.topbar {
  height: 64px; flex-shrink: 0;
  background: #fff; border-bottom: 1px solid var(--outline-variant);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04); z-index: 40;
}
.topbar-title { font-size: 18px; font-weight: 700; color: var(--on-surface); }

.content-area { flex: 1; display: flex; overflow: hidden; }
.map-wrap { flex: 1; position: relative; overflow: hidden; padding: 16px; }
</style>
