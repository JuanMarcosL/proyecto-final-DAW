<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Dashboard</h1>
      <div class="dashboard-controls">
        <select v-model="period" @change="loadData" class="period-select">
          <option value="all">Histórico</option>
          <option value="today">Hoy</option>
          <option value="week">Esta semana</option>
          <option value="month">Este mes</option>
        </select>
        <span class="dashboard-date">{{ today }}</span>
      </div>
    </div>

    <div v-if="loading" class="dash-loading">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-card--blue">
          <div class="kpi-icon">📋</div>
          <div class="kpi-info">
            <span class="kpi-label">Órdenes de Trabajo</span>
            <span class="kpi-value">{{ data.work_orders?.total }}</span>
          </div>
          <div class="kpi-breakdown">
            <span class="kpi-sub kpi-sub--open">{{ data.work_orders?.open }} abiertas</span>
            <span class="kpi-sub kpi-sub--progress">{{ data.work_orders?.in_progress }} en progreso</span>
            <span class="kpi-sub kpi-sub--closed">{{ data.work_orders?.closed }} cerradas</span>
          </div>
        </div>

        <div class="kpi-card kpi-card--green">
          <div class="kpi-icon">📅</div>
          <div class="kpi-info">
            <span class="kpi-label">Citas de Servicio</span>
            <span class="kpi-value">{{ data.appointments?.total }}</span>
          </div>
          <div class="kpi-breakdown">
            <span class="kpi-sub">{{ data.appointments?.scheduled }} programadas</span>
            <span class="kpi-sub kpi-sub--closed">{{ data.appointments?.completed }} completadas</span>
          </div>
        </div>

        <div class="kpi-card kpi-card--purple">
          <div class="kpi-icon">👷</div>
          <div class="kpi-info">
            <span class="kpi-label">Técnicos</span>
            <span class="kpi-value">{{ data.resources?.total }}</span>
          </div>
          <div class="kpi-breakdown">
            <span class="kpi-sub kpi-sub--closed">{{ data.resources?.active }} activos</span>
          </div>
        </div>

        <div class="kpi-card kpi-card--orange">
          <div class="kpi-icon">🏖️</div>
          <div class="kpi-info">
            <span class="kpi-label">Ausencias</span>
            <span class="kpi-value">{{ data.absences?.pending }}</span>
          </div>
          <div class="kpi-breakdown">
            <span class="kpi-sub kpi-sub--open">pendientes de aprobar</span>
            <span class="kpi-sub kpi-sub--closed">{{ data.absences?.approved }} aprobadas</span>
          </div>
        </div>
      </div>

      <!-- Recent Work Orders -->
      <div class="dash-card">
        <div class="dash-card__header">
          <h2>Últimas ordenes creadas</h2>
          <router-link to="/work-orders" class="dash-link">Ver todas →</router-link>
        </div>
        <table class="dash-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Estado</th>
              <th>Prioridad</th>
              <th>Creada por</th>
              <th>Fecha de creación</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="wo in data.recent_work_orders" :key="wo.id">
              <td class="dash-id">{{ wo.id }}</td>
              <td>
                <router-link :to="`/work-orders/${wo.id}`" class="dash-wo-link">{{ wo.title }}</router-link>
              </td>
              <td><span :class="['badge', `badge--${wo.status}`]">{{ statusLabel(wo.status) }}</span></td>
              <td><span :class="['badge', `badge--priority-${wo.priority}`]">{{ priorityLabel(wo.priority) }}</span>
              </td>
              <td>{{ wo.creator?.name || '-' }}</td>
              <td>{{ formatDate(wo.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const loading = ref(false)
const data = ref({})
const period = ref('all')

const today = new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

async function loadData() {
  loading.value = true
  try {
    const res = await api.get('/dashboard', { params: { period: period.value } })
    data.value = res.data
  } catch (e) {
    console.error('Error cargando dashboard', e)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

function statusLabel(s) {
  return { open: 'Abierta', in_progress: 'En progreso', closed: 'Cerrada', cancelled: 'Cancelada' }[s] || s
}
function priorityLabel(p) {
  return { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' }[p] || p
}
function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<style scoped>
@import '../assets/css/dashboard.css';
</style>
