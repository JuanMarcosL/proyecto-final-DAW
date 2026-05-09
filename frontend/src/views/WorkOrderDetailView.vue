<template>
  <div class="wo-detail-page">

    <!-- Header -->
    <div class="detail-header">
      <button class="btn-back" @click="$router.push('/work-orders')">← Volver</button>
      <div class="detail-header__right">
        <button class="btn-edit" @click="openEditModal">✏️ Editar</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="wo-loading">
      <div class="spinner"></div>
      <span>Cargando...</span>
    </div>

    <template v-else-if="workOrder">

      <!-- Info Card -->
      <div class="detail-card">
        <div class="detail-card__header">
          <div>
            <h1 class="detail-title">{{ workOrder.title }}</h1>
            <span class="detail-id">#{{ workOrder.id }}</span>
          </div>
          <div class="detail-badges">
            <span :class="['badge', `badge--${workOrder.status}`]">{{ statusLabel(workOrder.status) }}</span>
            <span :class="['badge', `badge--priority-${workOrder.priority}`]">{{ priorityLabel(workOrder.priority)
              }}</span>
          </div>
        </div>

        <div class="detail-grid">
          <div class="detail-field">
            <label>Descripción</label>
            <p>{{ workOrder.description || '-' }}</p>
          </div>
          <div class="detail-field">
            <label>Dirección</label>
            <p>{{ workOrder.address || '-' }}</p>
          </div>
          <div class="detail-field">
            <label>Fecha límite</label>
            <p>{{ formatDate(workOrder.due_date) }}</p>
          </div>
          <div class="detail-field">
            <label>Creada por</label>
            <p>{{ workOrder.creator?.name || '-' }}</p>
          </div>
          <div class="detail-field">
            <label>Último modificador</label>
            <p>{{ workOrder.updater?.name || '-' }}</p>
          </div>
          <div class="detail-field">
            <label>Fecha creación</label>
            <p>{{ formatDate(workOrder.created_at) }}</p>
          </div>
          <div class="detail-field">
            <label>Última modificación</label>
            <p>{{ formatDate(workOrder.updated_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Mapa -->
      <div v-if="workOrder.latitude && workOrder.longitude" class="detail-map-card">
        <h2 class="section-title">Ubicación</h2>
        <div ref="mapContainer" class="map-container"></div>
      </div>

      <!-- Service Appointments -->
      <div class="detail-card">
        <div class="sa-header">
          <h2 class="section-title">Citas de Servicio</h2>
          <button class="btn-new-sa" @click="openSAModal()">+ Nueva cita</button>
        </div>

        <div v-if="workOrder.appointments?.length === 0" class="sa-empty">
          No hay citas de servicio aún.
        </div>

        <table v-else class="sa-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Estado</th>
              <th>Técnico asignado</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Notas</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sa in workOrder.appointments" :key="sa.id" class="sa-row">
              <td class="sa-id">{{ sa.id }}</td>
              <td>
                <span :class="['badge', `badge--sa-${sa.status}`]">{{ saStatusLabel(sa.status) }}</span>
              </td>
              <td>{{ sa.resource?.user?.name || '-' }}</td>
              <td>{{ formatDateTime(sa.scheduled_start) }}</td>
              <td>{{ formatDateTime(sa.scheduled_end) }}</td>
              <td>{{ sa.notes || ' ' }}</td>
              <td class="sa-actions">
                <button class="btn-icon" @click="openSAModal(sa)" title="Editar">✏️</button>
                <button class="btn-icon btn-icon--delete" @click="confirmDeleteSA(sa)" title="Eliminar">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </template>

    <!-- Modal Editar OT -->
    <div v-if="showEditModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>Editar OT</h2>
          <button class="modal__close" @click="showEditModal = false">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveWO">
          <div class="form-group">
            <label>Título *</label>
            <input v-model="woForm.title" type="text" required />
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <textarea v-model="woForm.description" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Dirección</label>
            <input ref="woAddressInput" v-model="woForm.address" type="text" autocomplete="off" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Estado</label>
              <select v-model="woForm.status">
                <option value="open">Abierta</option>
                <option value="in_progress">En progreso</option>
                <option value="closed">Cerrada</option>
                <option value="cancelled">Cancelada</option>
              </select>
            </div>
            <div class="form-group">
              <label>Prioridad</label>
              <select v-model="woForm.priority">
                <option value="low">Baja</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Fecha límite</label>
            <input v-model="woForm.due_date" type="datetime-local" />
          </div>
          <div v-if="woFormError" class="form-error">{{ woFormError }}</div>
          <div class="modal__footer">
            <button type="button" class="btn-cancel" @click="showEditModal = false">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal SA -->
    <div v-if="showSAModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editingSA ? 'Editar cita' : 'Nueva cita' }}</h2>
          <button class="modal__close" @click="showSAModal = false">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveSA">
          <div class="form-group">
            <label>Dirección</label>
            <input :value="workOrder.address || '-'" type="text" readonly
              style="background: var(--color-surface2); cursor: not-allowed;" />
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea v-model="saForm.notes" rows="3" placeholder="Instrucciones, observaciones..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Inicio</label>
              <input v-model="saForm.scheduled_start" type="datetime-local" />
            </div>
            <div class="form-group">
              <label>Fin</label>
              <input v-model="saForm.scheduled_end" type="datetime-local" />
            </div>
          </div>
          <div class="form-group">
            <label>Técnico</label>
            <select v-model="saForm.resource_id">
              <option value="">Sin asignar</option>
              <option v-for="r in resources" :key="r.id" :value="r.id">
                {{ r.user?.name }} — {{ r.specialty }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Estado</label>
            <select v-model="saForm.status">
              <option value="draft">Borrador</option>
              <option value="scheduled">Programada</option>
              <option value="in_progress">En progreso</option>
              <option value="completed">Completada</option>
              <option value="cancelled">Cancelada</option>
            </select>
          </div>
          <div v-if="saFormError" class="form-error">{{ saFormError }}</div>
          <div class="modal__footer">
            <button type="button" class="btn-cancel" @click="showSAModal = false">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Confirmar Borrado SA -->
    <div v-if="showDeleteSAModal" class="modal-overlay">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Eliminar cita</h2>
          <button class="modal__close" @click="showDeleteSAModal = false">×</button>
        </div>
        <p class="delete-msg">¿Seguro que quieres eliminar la cita <strong>#{{ deletingSA?.id }}</strong>?</p>
        <div class="modal__footer">
          <button class="btn-cancel" @click="showDeleteSAModal = false">Cancelar</button>
          <button class="btn-delete" :disabled="saving" @click="deleteSA">{{ saving ? 'Eliminando...' : 'Eliminar'
            }}</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { workOrdersService } from '../services/workorders'
import { appointmentsService } from '../services/appointments'
import { resourcesService } from '../services/resources'

const route = useRoute()
const workOrder = ref(null)
const loading = ref(false)
const resources = ref([])

// Map
const mapContainer = ref(null)

// Edit WO modal
const showEditModal = ref(false)
const woForm = ref({})
const woFormError = ref('')
const saving = ref(false)
const woAddressInput = ref(null)

// SA modal
const showSAModal = ref(false)
const editingSA = ref(null)
const saForm = ref(defaultSAForm())
const saFormError = ref('')

// Delete SA
const showDeleteSAModal = ref(false)
const deletingSA = ref(null)

function defaultSAForm() {
  return { address: workOrder.value?.address || '', resource_id: '', notes: '', scheduled_start: '', scheduled_end: '', status: 'draft' }
}

onMounted(async () => {
  await fetchWO()
  if (workOrder.value?.latitude && workOrder.value?.longitude) {
    await nextTick()
    initMap()
  }
  const resRes = await resourcesService.getAll()
  resources.value = resRes.data
})

async function fetchWO() {
  loading.value = true
  try {
    const res = await workOrdersService.getOne(route.params.id)
    workOrder.value = res.data
  } catch (e) {
    console.error('Error cargando OT', e)
  } finally {
    loading.value = false
  }
}

function initMap() {
  if (!window.google || !mapContainer.value) return
  const pos = { lat: parseFloat(workOrder.value.latitude), lng: parseFloat(workOrder.value.longitude) }
  const map = new window.google.maps.Map(mapContainer.value, { center: pos, zoom: 15 })
  new window.google.maps.Marker({ position: pos, map })
}

// Edit WO
function openEditModal() {
  woForm.value = {
    title: workOrder.value.title,
    description: workOrder.value.description || '',
    address: workOrder.value.address || '',
    status: workOrder.value.status,
    priority: workOrder.value.priority,
    due_date: workOrder.value.due_date ? workOrder.value.due_date.slice(0, 16) : '',
  }
  woFormError.value = ''
  showEditModal.value = true
  nextTick(() => initWOAutocomplete())
}

function initWOAutocomplete() {
  if (!woAddressInput.value || !window.google) return
  const ac = new window.google.maps.places.Autocomplete(woAddressInput.value, {
    types: ['address'],
    fields: ['formatted_address', 'geometry']
  })
  ac.addListener('place_changed', () => {
    const place = ac.getPlace()
    woForm.value.address = place.formatted_address
    woForm.value.latitude = place.geometry.location.lat()
    woForm.value.longitude = place.geometry.location.lng()
  })
}

async function saveWO() {
  woFormError.value = ''
  saving.value = true
  try {
    const res = await workOrdersService.update(workOrder.value.id, woForm.value)
    workOrder.value = res.data
    showEditModal.value = false
    await nextTick()
    if (workOrder.value.latitude && workOrder.value.longitude) {
      initMap()
    }
  } catch (e) {
    woFormError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

// SA
function openSAModal(sa = null) {
  editingSA.value = sa
  saForm.value = sa ? {
    resource_id: sa.resource_id || '',
    notes: sa.notes || '',
    scheduled_start: sa.scheduled_start ? sa.scheduled_start.slice(0, 16) : '',
    scheduled_end: sa.scheduled_end ? sa.scheduled_end.slice(0, 16) : '',
    status: sa.status,
  } : defaultSAForm()
  saFormError.value = ''
  showSAModal.value = true
}

async function saveSA() {
  saFormError.value = ''
  saving.value = true
  try {
    if (editingSA.value) {
      const res = await appointmentsService.update(editingSA.value.id, {
        ...saForm.value,
        address: workOrder.value.address
      })
      const idx = workOrder.value.appointments.findIndex(a => a.id === editingSA.value.id)
      workOrder.value.appointments[idx] = res.data
    } else {
      const res = await appointmentsService.create({
        ...saForm.value,
        work_order_id: workOrder.value.id,
        address: workOrder.value.address
      })
      workOrder.value.appointments.push(res.data)
    }
    showSAModal.value = false
  } catch (e) {
    saFormError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

function confirmDeleteSA(sa) {
  deletingSA.value = sa
  showDeleteSAModal.value = true
}

async function deleteSA() {
  saving.value = true
  try {
    await appointmentsService.delete(deletingSA.value.id)
    workOrder.value.appointments = workOrder.value.appointments.filter(a => a.id !== deletingSA.value.id)
    showDeleteSAModal.value = false
  } catch (e) {
    console.error('Error eliminando SA', e)
  } finally {
    saving.value = false
  }
}

// Helpers
function statusLabel(s) {
  return { open: 'Abierta', in_progress: 'En progreso', closed: 'Cerrada', cancelled: 'Cancelada' }[s] || s
}
function priorityLabel(p) {
  return { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' }[p] || p
}
function saStatusLabel(s) {
  return { draft: 'Borrador', scheduled: 'Programada', in_progress: 'En progreso', completed: 'Completada', cancelled: 'Cancelada' }[s] || s
}
function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
function formatDateTime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
@import '../assets/css/workorderdetail.css';
</style>
