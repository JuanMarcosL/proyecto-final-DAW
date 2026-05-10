<template>
  <div class="calendar-page">

    <div class="calendar-header">
      <h1 class="calendar-title">Calendario</h1>
      <div class="calendar-legend">
        <span class="legend-item"><span class="legend-dot legend-dot--scheduled"></span>Programada</span>
        <span class="legend-item"><span class="legend-dot legend-dot--in_progress"></span>En progreso</span>
        <span class="legend-item"><span class="legend-dot legend-dot--completed"></span>Completada</span>
        <span class="legend-item"><span class="legend-dot legend-dot--absence"></span>Ausencia</span>
      </div>
    </div>

    <div class="calendar-wrap">
      <FullCalendar :options="calendarOptions" />
    </div>

    <!-- Modal detalle evento -->
    <div v-if="showEventModal" class="modal-overlay" @click.self="showEventModal = false">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>{{ selectedEvent?.title }}</h2>
          <button class="modal__close" @click="showEventModal = false">×</button>
        </div>
        <div class="event-detail">
          <div class="event-detail__row" v-if="selectedEvent?.extendedProps?.type === 'appointment'">
            <span class="event-detail__label">Orden de Trabajo</span>
            <span class="event-detail__value">{{ selectedEvent.extendedProps.workOrder }}</span>
          </div>
          <div class="event-detail__row" v-if="selectedEvent?.extendedProps?.type === 'appointment'">
            <span class="event-detail__label">Técnico</span>
            <span class="event-detail__value">{{ selectedEvent.extendedProps.resource }}</span>
          </div>
          <div class="event-detail__row">
            <span class="event-detail__label">Inicio</span>
            <span class="event-detail__value">{{ formatDateTime(selectedEvent?.start) }}</span>
          </div>
          <div class="event-detail__row">
            <span class="event-detail__label">Fin</span>
            <span class="event-detail__value">{{ formatDateTime(selectedEvent?.end) }}</span>
          </div>
          <div class="event-detail__row" v-if="selectedEvent?.extendedProps?.status">
            <span class="event-detail__label">Estado</span>
            <span class="event-detail__value">{{ selectedEvent.extendedProps.status }}</span>
          </div>
          <div class="event-detail__row" v-if="selectedEvent?.extendedProps?.notes">
            <span class="event-detail__label">Notas</span>
            <span class="event-detail__value">{{ selectedEvent.extendedProps.notes }}</span>
          </div>
        </div>
        <div class="modal__footer" v-if="selectedEvent?.extendedProps?.type === 'appointment'">
          <button class="btn-cancel" @click="showEventModal = false">Cerrar</button>
          <router-link :to="`/work-orders/${selectedEvent.extendedProps.workOrderId}`" class="btn-save"
            @click="showEventModal = false">
            Ver OT
          </router-link>
        </div>
        <div class="modal__footer" v-else>
          <button class="btn-cancel" @click="showEventModal = false">Cerrar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import esLocale from '@fullcalendar/core/locales/es'
import api from '../services/api'

const showEventModal = ref(false)
const selectedEvent = ref(null)
const events = ref([])

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: esLocale,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  events: [],
  eventClick: handleEventClick,
  height: 'auto',
  slotMinTime: '07:00:00',
  slotMaxTime: '21:00:00',
  allDaySlot: true,
  nowIndicator: true,
  eventDisplay: 'block',
})

onMounted(async () => {
  await loadEvents()
})

async function loadEvents() {
  try {
    const [appsRes, absRes] = await Promise.all([
      api.get('/appointments'),
      api.get('/absences')
    ])

    const appointmentEvents = appsRes.data
      .filter(a => a.scheduled_start && a.scheduled_end)
      .map(a => ({
        id: `app-${a.id}`,
        title: a.work_order?.title || `Cita #${a.id}`,
        start: a.scheduled_start,
        end: a.scheduled_end,
        backgroundColor: statusColor(a.status),
        borderColor: statusColor(a.status),
        extendedProps: {
          type: 'appointment',
          status: saStatusLabel(a.status),
          workOrder: a.work_order?.title || '-',
          workOrderId: a.work_order_id,
          resource: a.resource?.user?.name || 'Sin técnico',
          notes: a.notes,
        }
      }))

    const absenceEvents = absRes.data
      .filter(a => a.status === 'approved')
      .map(a => ({
        id: `abs-${a.id}`,
        title: `🏖️ ${a.resource?.user?.name || 'Técnico'} — ${typeLabel(a.type)}`,
        start: a.start_date,
        end: addDay(a.end_date),
        allDay: true,
        backgroundColor: '#94a3b8',
        borderColor: '#64748b',
        extendedProps: {
          type: 'absence',
          status: null,
          notes: a.reason,
        }
      }))

    calendarOptions.value.events = [...appointmentEvents, ...absenceEvents]
  } catch (e) {
    console.error('Error cargando eventos', e)
  }
}

function handleEventClick(info) {
  selectedEvent.value = info.event
  showEventModal.value = true
}

function statusColor(status) {
  return {
    draft: '#94a3b8',
    scheduled: '#0176d3',
    in_progress: '#f59e0b',
    completed: '#04844b',
    cancelled: '#c23934',
  }[status] || '#94a3b8'
}

function saStatusLabel(s) {
  return { draft: 'Borrador', scheduled: 'Programada', in_progress: 'En progreso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s
}

function typeLabel(t) {
  return { vacation: 'Vacaciones', medical: 'Médica', personal: 'Personal', other: 'Otro' }[t] || t
}

function addDay(dateStr) {
  const d = new Date(dateStr)
  d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
}

function formatDateTime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
@import '../assets/css/calendar.css';
</style>
