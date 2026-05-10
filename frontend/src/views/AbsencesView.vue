<template>
  <div class="absences-page">

    <!-- Header -->
    <div class="absences-header">
      <div class="absences-header__left">
        <h1 class="absences-title">Ausencias</h1>
        <span class="absences-count">{{ absences.length }} registros</span>
      </div>
      <button class="btn-new" @click="openModal()" :disabled="isTecnico && !myResource">
  + Nueva ausencia
</button>
    </div>

    <!-- Filters -->
    <div class="absences-filters">
      <select v-model="filterStatus" class="filter-select">
        <option value="">Todos los estados</option>
        <option value="pending">Pendiente</option>
        <option value="approved">Aprobada</option>
        <option value="rejected">Rechazada</option>
      </select>
      <select v-model="filterType" class="filter-select">
        <option value="">Todos los tipos</option>
        <option value="vacation">Vacaciones</option>
        <option value="medical">Médica</option>
        <option value="personal">Personal</option>
        <option value="other">Otro</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="abs-loading">
      <div class="spinner"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="abs-empty">
      <div class="abs-empty__icon">🏖️</div>
      <p>No hay ausencias registradas</p>
    </div>

    <!-- Table -->
    <div v-else class="abs-table-wrap">
      <table class="abs-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Técnico</th>
            <th>Tipo</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Días</th>
            <th>Motivo</th>
            <th>Estado</th>
            <th>Resuelto por</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in filtered" :key="a.id" class="abs-row">
            <td class="abs-id">{{ a.id }}</td>
            <td class="abs-tecnico">{{ a.resource?.user?.name || '-' }}</td>
            <td><span :class="['badge-type', `badge-type--${a.type}`]">{{ typeLabel(a.type) }}</span></td>
            <td>{{ formatDate(a.start_date) }}</td>
            <td>{{ formatDate(a.end_date) }}</td>
            <td>{{ daysDiff(a.start_date, a.end_date) }}</td>
            <td class="abs-reason">{{ a.reason || '-' }}</td>
            <td><span :class="['badge', `badge--abs-${a.status}`]">{{ statusLabel(a.status) }}</span></td>
            <td>{{ a.resolver?.name || '-' }}</td>
            <td class="abs-actions">
              <template v-if="!isTecnico">
                <template v-if="a.status === 'pending' && canApprove">
                  <button class="btn-approve" @click="resolve(a, 'approved')" title="Aprobar">✓</button>
                  <button class="btn-reject" @click="resolve(a, 'rejected')" title="Rechazar">✗</button>
                  <button class="btn-icon" @click="openEditModal(a)" title="Editar">✏️</button>
                </template>
                <button class="btn-icon btn-icon--delete" @click="confirmDelete(a)" title="Eliminar">🗑️</button>
              </template>
              <template v-else>
                <template v-if="a.status === 'pending'">
                  <button class="btn-icon" @click="openEditModal(a)" title="Editar">✏️</button>
                  <button class="btn-icon btn-icon--delete" @click="confirmDelete(a)" title="Eliminar">🗑️</button>
                </template>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Nueva/Editar Ausencia -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editingAbsence ? 'Editar ausencia' : 'Nueva ausencia' }}</h2>
          <button class="modal__close" @click="showModal = false">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveAbsence">
          <div class="form-group" v-if="!isTecnico">
            <label>Técnico</label>
            <select v-model="form.resource_id" required :disabled="!!editingAbsence">
              <option value="">Selecciona un técnico</option>
              <option v-for="r in resources" :key="r.id" :value="r.id">{{ r.user?.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select v-model="form.type" required>
              <option value="vacation">Vacaciones</option>
              <option value="medical">Médica</option>
              <option value="personal">Personal</option>
              <option value="other">Otro</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Desde</label>
              <input v-model="form.start_date" type="date" required :min="isTecnico ? today : undefined" />
            </div>
            <div class="form-group">
              <label>Hasta</label>
              <input v-model="form.end_date" type="date" required
                :min="isTecnico ? (form.start_date || today) : undefined" />
            </div>
          </div>
          <div class="form-group">
            <label>Motivo</label>
            <textarea v-model="form.reason" rows="3" placeholder="Descripción opcional..."></textarea>
          </div>
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="modal__footer">
            <button type="button" class="btn-cancel" @click="showModal = false">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Confirmar Borrado -->
    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Eliminar ausencia</h2>
          <button class="modal__close" @click="showDeleteModal = false">×</button>
        </div>
        <p class="delete-msg">¿Seguro que quieres eliminar esta ausencia de <strong>{{
          deletingAbsence?.resource?.user?.name
            }}</strong>?</p>
        <div class="modal__footer">
          <button class="btn-cancel" @click="showDeleteModal = false">Cancelar</button>
          <button class="btn-delete" :disabled="saving" @click="deleteAbsence">{{ saving ? 'Eliminando...' : 'Eliminar'
          }}</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { resourcesService } from '../services/resources'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const absences = ref([])
const resources = ref([])
const loading = ref(false)
const filterStatus = ref('')
const filterType = ref('')

const showModal = ref(false)
const saving = ref(false)
const formError = ref('')
const form = ref(defaultForm())
const editingAbsence = ref(null)

const showDeleteModal = ref(false)
const deletingAbsence = ref(null)
const today = new Date().toISOString().split('T')[0]

const canApprove = ref(false)

const isTecnico = computed(() => auth.user?.role === 'tecnico')
const myResource = ref(null)

onMounted(async () => {
  await auth.fetchMe()
  canApprove.value = ['admin', 'supervisor'].includes(auth.user?.role)

  if (auth.user?.role === 'tecnico') {
    let intentos = 0
    while (!myResource.value?.id && intentos < 3) {
      try {
        const res = await api.get('/my-resource')
        myResource.value = res.data
      } catch (e) {
        console.error('Error cargando my-resource:', e)
      }
      intentos++
      if (!myResource.value?.id) await new Promise(r => setTimeout(r, 500))
    }
    await fetchAbsences()
  } else {
    await Promise.all([fetchAbsences(), fetchResources()])
  }
})

function defaultForm() {
  return { resource_id: '', type: 'vacation', start_date: '', end_date: '', reason: '' }
}

const filtered = computed(() => {
  return absences.value.filter(a => {
    const matchStatus = !filterStatus.value || a.status === filterStatus.value
    const matchType = !filterType.value || a.type === filterType.value
    return matchStatus && matchType
  })
})

async function fetchAbsences() {
  loading.value = true
  try {
    const res = await api.get('/absences')
    absences.value = res.data
  } catch (e) {
    console.error('Error cargando ausencias', e)
  } finally {
    loading.value = false
  }
}

async function fetchResources() {
  try {
    const res = await resourcesService.getAll()
    resources.value = res.data
  } catch (e) {
    console.error('Error cargando recursos', e)
  }
}

function openModal() {
  console.log('myResource al abrir modal:', myResource.value)
  editingAbsence.value = null
  form.value = defaultForm()
  if (isTecnico.value && myResource.value) {
    form.value.resource_id = myResource.value.id
  }
  formError.value = ''
  showModal.value = true
}

function openEditModal(a) {
  editingAbsence.value = a
  form.value = {
    resource_id: a.resource_id,
    type: a.type,
    start_date: a.start_date,
    end_date: a.end_date,
    reason: a.reason || '',
  }
  formError.value = ''
  showModal.value = true
}

async function saveAbsence() {
  console.log('form al guardar:', form.value)
  saving.value = true
  formError.value = ''
  try {
    if (editingAbsence.value) {
      const res = await api.put(`/absences/${editingAbsence.value.id}`, form.value)
      const idx = absences.value.findIndex(a => a.id === editingAbsence.value.id)
      absences.value[idx] = res.data
    } else {
      await api.post('/absences', form.value)
      await fetchAbsences()
    }
    showModal.value = false
  } catch (e) {
    formError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

async function resolve(absence, status) {
  try {
    const res = await api.put(`/absences/${absence.id}`, { status })
    const idx = absences.value.findIndex(a => a.id === absence.id)
    absences.value[idx] = res.data
  } catch (e) {
    console.error('Error resolviendo ausencia', e)
  }
}

function confirmDelete(a) {
  deletingAbsence.value = a
  showDeleteModal.value = true
}

async function deleteAbsence() {
  saving.value = true
  try {
    await api.delete(`/absences/${deletingAbsence.value.id}`)
    absences.value = absences.value.filter(a => a.id !== deletingAbsence.value.id)
    showDeleteModal.value = false
  } catch (e) {
    console.error('Error eliminando ausencia', e)
  } finally {
    saving.value = false
  }
}

function daysDiff(start, end) {
  if (!start || !end) return '-'
  const diff = Math.ceil((new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24)) + 1
  return `${diff}d`
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function typeLabel(t) {
  return { vacation: 'Vacaciones', medical: 'Médica', personal: 'Personal', other: 'Otro' }[t] || t
}

function statusLabel(s) {
  return { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada' }[s] || s
}

</script>

<style scoped>
@import '../assets/css/absences.css';
</style>
