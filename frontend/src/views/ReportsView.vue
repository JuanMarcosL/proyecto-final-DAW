<template>
  <div class="reports-page">

    <div class="reports-header">
      <h1 class="reports-title">Reportes</h1>
      <div class="reports-controls">
        <select v-model="selectedReport" class="report-select">
          <option value="wo_status">OTs por estado</option>
          <option value="wo_priority">OTs por prioridad</option>
          <option value="appointments_resource">Citas por técnico</option>
          <option value="absences_resource">Ausencias por técnico</option>
          <option value="wo_table">Listado de OTs</option>
        </select>
        <button class="btn-export" @click="exportPDF">⬇ Exportar PDF</button>
      </div>
    </div>

    <div v-if="loading" class="rep-loading">
      <div class="spinner"></div>
    </div>

    <template v-else>

      <!-- OTs por estado -->
      <div v-if="selectedReport === 'wo_status'" class="chart-card">
        <h2 class="chart-title">Órdenes de Trabajo por Estado</h2>
        <div class="chart-wrap">
          <Doughnut :data="woStatusData" :options="doughnutOptions" />
        </div>
        <div class="chart-summary">
          <div v-for="(val, key) in data.wo_by_status" :key="key" class="summary-item">
            <span :class="['summary-dot', `summary-dot--${key}`]"></span>
            <span class="summary-label">{{ statusLabel(key) }}</span>
            <span class="summary-value">{{ val }}</span>
          </div>
        </div>
      </div>

      <!-- OTs por prioridad -->
      <div v-if="selectedReport === 'wo_priority'" class="chart-card">
        <h2 class="chart-title">Órdenes de Trabajo por Prioridad</h2>
        <div class="chart-wrap">
          <Bar :data="woPriorityData" :options="barOptions" />
        </div>
      </div>

      <!-- Citas por técnico -->
      <div v-if="selectedReport === 'appointments_resource'" class="chart-card">
        <h2 class="chart-title">Citas por Técnico</h2>
        <div class="chart-wrap">
          <Bar :data="appointmentsResourceData" :options="barOptions" />
        </div>
      </div>

      <!-- Ausencias por técnico -->
      <div v-if="selectedReport === 'absences_resource'" class="chart-card">
        <h2 class="chart-title">Días de Ausencia por Técnico (aprobadas)</h2>
        <div class="chart-wrap">
          <Bar :data="absencesResourceData" :options="barOptions" />
        </div>
      </div>

      <!-- Listado de OTs -->
      <div v-if="selectedReport === 'wo_table'" class="chart-card">
        <h2 class="chart-title">Listado completo de Órdenes de Trabajo</h2>
        <table class="rep-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Estado</th>
              <th>Prioridad</th>
              <th>Dirección</th>
              <th>Creada por</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="wo in data.work_orders" :key="wo.id">
              <td>{{ wo.id }}</td>
              <td>{{ wo.title }}</td>
              <td><span :class="['badge', `badge--${wo.status}`]">{{ statusLabel(wo.status) }}</span></td>
              <td><span :class="['badge', `badge--priority-${wo.priority}`]">{{ priorityLabel(wo.priority) }}</span></td>
              <td>{{ wo.address || '-' }}</td>
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
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title, Tooltip, Legend,
  BarElement, CategoryScale, LinearScale,
  ArcElement
} from 'chart.js'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import api from '../services/api'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const loading = ref(false)
const data = ref({})
const selectedReport = ref('wo_status')

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get('/reports')
    data.value = res.data
  } catch (e) {
    console.error('Error cargando reportes', e)
  } finally {
    loading.value = false
  }
})

// Chart data
const woStatusData = computed(() => ({
  labels: ['Abierta', 'En progreso', 'Cerrada', 'Cancelada'],
  datasets: [{
    data: [
      data.value.wo_by_status?.open || 0,
      data.value.wo_by_status?.in_progress || 0,
      data.value.wo_by_status?.closed || 0,
      data.value.wo_by_status?.cancelled || 0,
    ],
    backgroundColor: ['#0176d3', '#f59e0b', '#04844b', '#c23934'],
  }]
}))

const woPriorityData = computed(() => ({
  labels: ['Baja', 'Media', 'Alta', 'Crítica'],
  datasets: [{
    label: 'Órdenes',
    data: [
      data.value.wo_by_priority?.low || 0,
      data.value.wo_by_priority?.medium || 0,
      data.value.wo_by_priority?.high || 0,
      data.value.wo_by_priority?.critical || 0,
    ],
    backgroundColor: ['#94a3b8', '#f59e0b', '#f97316', '#c23934'],
  }]
}))

const appointmentsResourceData = computed(() => ({
  labels: data.value.appointments_by_resource?.map(r => r.name) || [],
  datasets: [{
    label: 'Citas asignadas',
    data: data.value.appointments_by_resource?.map(r => r.total) || [],
    backgroundColor: '#0176d3',
  }]
}))

const absencesResourceData = computed(() => ({
  labels: data.value.absences_by_resource?.map(r => r.name) || [],
  datasets: [{
    label: 'Días de ausencia',
    data: data.value.absences_by_resource?.map(r => r.days) || [],
    backgroundColor: '#7c3aed',
  }]
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
}

// Export PDF
function exportPDF() {
  const doc = new jsPDF()
  const reportTitles = {
    wo_status: 'OTs por Estado',
    wo_priority: 'OTs por Prioridad',
    appointments_resource: 'Citas por Técnico',
    absences_resource: 'Ausencias por Técnico',
    wo_table: 'Listado de Órdenes de Trabajo',
  }

  doc.setFontSize(16)
  doc.text(`Field Service — ${reportTitles[selectedReport.value]}`, 14, 20)
  doc.setFontSize(10)
  doc.text(`Generado: ${new Date().toLocaleString('es-ES')}`, 14, 28)

  if (selectedReport.value === 'wo_status') {
    autoTable(doc, {
      startY: 35,
      head: [['Estado', 'Total']],
      body: [
        ['Abierta', data.value.wo_by_status?.open],
        ['En progreso', data.value.wo_by_status?.in_progress],
        ['Cerrada', data.value.wo_by_status?.closed],
        ['Cancelada', data.value.wo_by_status?.cancelled],
      ]
    })
  } else if (selectedReport.value === 'wo_priority') {
    autoTable(doc, {
      startY: 35,
      head: [['Prioridad', 'Total']],
      body: [
        ['Baja', data.value.wo_by_priority?.low],
        ['Media', data.value.wo_by_priority?.medium],
        ['Alta', data.value.wo_by_priority?.high],
        ['Crítica', data.value.wo_by_priority?.critical],
      ]
    })
  } else if (selectedReport.value === 'appointments_resource') {
    autoTable(doc, {
      startY: 35,
      head: [['Técnico', 'Citas asignadas']],
      body: data.value.appointments_by_resource?.map(r => [r.name, r.total]) || []
    })
  } else if (selectedReport.value === 'absences_resource') {
    autoTable(doc, {
      startY: 35,
      head: [['Técnico', 'Días de ausencia']],
      body: data.value.absences_by_resource?.map(r => [r.name, r.days]) || []
    })
  } else if (selectedReport.value === 'wo_table') {
    autoTable(doc, {
      startY: 35,
      head: [['#', 'Título', 'Estado', 'Prioridad', 'Dirección', 'Creada por', 'Fecha']],
      body: data.value.work_orders?.map(wo => [
        wo.id,
        wo.title,
        statusLabel(wo.status),
        priorityLabel(wo.priority),
        wo.address || '-',
        wo.creator?.name || '-',
        formatDate(wo.created_at),
      ]) || []
    })
  }

  doc.save(`reporte-${selectedReport.value}-${Date.now()}.pdf`)
}

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
@import '../assets/css/reports.css';
</style>
