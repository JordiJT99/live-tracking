<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../composables/useToast'

const auth = useAuthStore()
const router = useRouter()
const { add: toast } = useToast()

const email = ref('demo@demo.com')
const password = ref('password')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Error al iniciar sesión'
    toast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

const features = [
  { icon: 'location_on',           text: 'Seguimiento GPS en tiempo real' },
  { icon: 'route',                 text: 'Gestión de rutas y polylines' },
  { icon: 'notifications_active',  text: 'Alertas de flota inteligentes' },
  { icon: 'analytics',             text: 'Panel de analítica de flota' },
]
</script>

<template>
  <div class="login-shell">

    <!-- Left panel — dark brand -->
    <div class="panel-left">
      <div class="panel-left-inner">
        <!-- Logo -->
        <div class="brand">
          <div class="brand-icon">
            <span class="material-symbols-outlined filled">directions_bus</span>
          </div>
          <div>
            <div class="brand-name">LiveTrack</div>
            <div class="brand-sub">Fleet Management</div>
          </div>
        </div>

        <!-- Hero text -->
        <div class="hero">
          <h1 class="hero-title">Monitorización de flotas en tiempo real</h1>
          <p class="hero-desc">Visualiza, analiza y controla toda tu flota desde un único panel.</p>
        </div>

        <!-- Feature list -->
        <ul class="features">
          <li v-for="f in features" :key="f.icon" class="feature">
            <div class="feature-icon">
              <span class="material-symbols-outlined filled">{{ f.icon }}</span>
            </div>
            <span>{{ f.text }}</span>
          </li>
        </ul>

        <!-- Bottom tagline -->
        <div class="panel-left-footer">
          <span class="material-symbols-outlined" style="font-size:14px;opacity:.5">lock</span>
          Conexión segura con Sanctum Bearer
        </div>
      </div>
    </div>

    <!-- Right panel — form -->
    <div class="panel-right">
      <div class="form-wrap">
        <div class="form-header">
          <h2 class="form-title">Bienvenido</h2>
          <p class="form-sub">Inicia sesión en tu cuenta de operador</p>
        </div>

        <form @submit.prevent="submit" class="form" novalidate>
          <div class="field">
            <label class="label" for="email">
              <span class="material-symbols-outlined field-label-icon">mail</span>
              Correo electrónico
            </label>
            <input
              id="email" v-model="email" type="email"
              class="input" autocomplete="email" required
              placeholder="demo@demo.com"
            />
          </div>

          <div class="field">
            <label class="label" for="password">
              <span class="material-symbols-outlined field-label-icon">lock</span>
              Contraseña
            </label>
            <input
              id="password" v-model="password" type="password"
              class="input" autocomplete="current-password" required
              placeholder="••••••••"
            />
          </div>

          <p v-if="error" class="error-msg">
            <span class="material-symbols-outlined" style="font-size:14px">error</span>
            {{ error }}
          </p>

          <button type="submit" class="btn-submit" :disabled="loading">
            <span v-if="loading" class="spinner" />
            <template v-else>
              <span class="material-symbols-outlined" style="font-size:18px">login</span>
              Iniciar sesión
            </template>
          </button>
        </form>

        <div class="demo-hint">
          <span class="material-symbols-outlined" style="font-size:13px">info</span>
          Credenciales demo: <code>demo@demo.com</code> / <code>password</code>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.login-shell {
  min-height: 100vh;
  display: flex;
  font-family: 'Inter', sans-serif;
}

/* ── Left panel ── */
.panel-left {
  width: 44%;
  background: #213145;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;
}
.panel-left-inner {
  max-width: 360px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.brand { display: flex; align-items: center; gap: 14px; }
.brand-icon {
  width: 44px; height: 44px; border-radius: 11px;
  background: #004ac6;
  display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0;
}
.brand-icon .material-symbols-outlined { font-size: 24px; }
.brand-name { font-size: 20px; font-weight: 700; color: #fff; line-height: 1.2; }
.brand-sub { font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(192,198,219,0.6); margin-top: 2px; }

.hero-title { font-size: 26px; font-weight: 700; color: #fff; line-height: 1.3; margin-bottom: 12px; }
.hero-desc  { font-size: 14px; color: rgba(192,198,219,0.75); line-height: 1.6; }

.features { list-style: none; display: flex; flex-direction: column; gap: 14px; }
.feature { display: flex; align-items: center; gap: 14px; }
.feature-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  background: rgba(0,74,198,0.35);
  display: flex; align-items: center; justify-content: center; color: #7eb3ff;
}
.feature-icon .material-symbols-outlined { font-size: 18px; }
.feature span:last-child { font-size: 13.5px; color: rgba(192,198,219,0.85); }

.panel-left-footer {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; color: rgba(192,198,219,0.4);
}

/* ── Right panel ── */
.panel-right {
  flex: 1;
  background: #f4f7fb;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 32px;
}
.form-wrap {
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 16px;
  padding: 40px 36px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.07), 0 1px 4px rgba(0,0,0,0.04);
  border: 1px solid #e5eaef;
}

.form-header { margin-bottom: 28px; }
.form-title { font-size: 22px; font-weight: 700; color: #0b1c30; margin-bottom: 6px; }
.form-sub   { font-size: 13px; color: #697584; }

.form { display: flex; flex-direction: column; gap: 18px; }

.field { display: flex; flex-direction: column; gap: 6px; }
.label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; color: #434655;
  text-transform: uppercase; letter-spacing: 0.05em;
}
.field-label-icon { font-size: 14px; color: #697584; }

.input {
  height: 42px; padding: 0 14px;
  border: 1px solid #c3c6d7; border-radius: 10px;
  font-size: 14px; color: #0b1c30; background: #fff;
  outline: none; font-family: 'Inter', sans-serif;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input::placeholder { color: #b0b8c4; }
.input:focus { border-color: #004ac6; box-shadow: 0 0 0 3px rgba(0,74,198,0.12); }

.error-msg {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: #ba1a1a;
  background: #fff5f5; border: 1px solid #fca5a5;
  border-radius: 8px; padding: 8px 12px;
}

.btn-submit {
  height: 44px; border-radius: 10px;
  background: #004ac6; color: #fff;
  border: none; cursor: pointer;
  font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: background 0.15s, transform 0.1s;
  margin-top: 4px;
}
.btn-submit:hover:not(:disabled) { background: #2563eb; }
.btn-submit:active:not(:disabled) { transform: scale(0.98); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.demo-hint {
  margin-top: 20px; padding: 10px 14px;
  background: #f4f7fb; border-radius: 8px; border: 1px solid #e5eaef;
  font-size: 11.5px; color: #697584;
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.demo-hint code {
  font-family: monospace; background: #e5eaef;
  padding: 1px 5px; border-radius: 3px; color: #004ac6;
}

/* Spinner */
.spinner {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  animation: spin 0.7s linear infinite;
  display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 700px) {
  .login-shell { flex-direction: column; }
  .panel-left { width: 100%; padding: 32px 24px; }
  .hero-title { font-size: 20px; }
  .features { display: none; }
}
</style>
