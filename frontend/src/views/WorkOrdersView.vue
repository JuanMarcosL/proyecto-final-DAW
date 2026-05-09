<template>
  <div class="wo-page">

    <!-- Header -->
    <div class="wo-header">
      <div class="wo-header__left">
        <h1 class="wo-title">Órdenes de Trabajo</h1>
        <span class="wo-count">{{ workOrders.length }} registros</span>
      </div>
      <button class="btn-new" @click="openModal()">
        <span class="btn-new__icon">+</span>
        Nueva OT
      </button>
    </div>

    <!-- Filters -->
    <div class="wo-filters">
      <input v-model="search" class="filter-input" placeholder="Buscar por título o descripción..." />
      <select v-model="filterStatus" class="filter-select">
        <option value="">Todos los estados</option>
        <option value="open">Abierta</option>
        <option value="in_progress">En progreso</option>
        <option value="closed">Cerrada</option>
        <option value="cancelled">Cancelada</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="wo-loading">
      <div class="spinner"></div>
      <span>Cargando órdenes...</span>
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="wo-empty">
      <div class="wo-empty__icon">📋</div>
      <p>No hay órdenes de trabajo</p>
      <button class="btn-new" @click="openModal()">Crear la primera</button>
    </div>

    <!-- Table -->
    <div v-else class="wo-table-wrap">
      <table class="wo-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Título</th>
            <th>Estado</th>
            <th>Prioridad</th>
            <th>Creada</th>
            <th>Creada por</th>
            <th>Último modificador</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="wo in filtered" :key="wo.id" class="wo-row">
            <td class="wo-id">{{ wo.id }}</td>
            <td class="wo-title-cell">
              <span class="wo-title-text">{{ wo.title }}</span>
              <span v-if="wo.description" class="wo-desc">{{ wo.description }}</span>
            </td>
            <td>
              <span :class="['badge', `badge--${wo.status}`]">
                {{ statusLabel(wo.status) }}
              </span>
            </td>
            <td>
              <span :class="['badge', `badge--priority-${wo.priority}`]">
                {{ priorityLabel(wo.priority) }}
              </span>
            </td>
            <td class="wo-date">{{ formatDate(wo.created_at) }}</td>
            <td>{{ wo.creator?.name || '-' }}</td>
            <td>{{ wo.updater?.name || '-' }}</td>
            <td class="wo-actions">
              <button class="btn-icon btn-icon--edit" @click="openModal(wo)" title="Editar">✏️</button>
              <button class="btn-icon btn-icon--delete" @click="confirmDelete(wo)" title="Eliminar">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editingWO ? 'Editar OT' : 'Nueva OT' }}</h2>
          <button class="modal__close" @click="closeModal">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveWO">
          <div class="form-group">
            <label>Título *</label>
            <input v-model="form.title" type="text" required placeholder="Ej: Instalación medidor zona norte" />
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <textarea v-model="form.description" rows="3" placeholder="Detalles de la intervención..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Estado</label>
              <select v-model="form.status">
                <option value="open">Abierta</option>
                <option value="in_progress">En progreso</option>
                <option value="closed">Cerrada</option>
                <option value="cancelled">Cancelada</option>
              </select>
            </div>
            <div class="form-group">
              <label>Prioridad</label>
              <select v-model="form.priority">
                <option value="low">Baja</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
          </div>
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="modal__footer">
            <button type="button" class="btn-cancel" @click="closeModal">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="saving">
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Confirmar Borrado -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Eliminar OT</h2>
          <button class="modal__close" @click="showDeleteModal = false">×</button>
        </div>
        <p class="delete-msg">¿Seguro que quieres eliminar "<strong>{{ deletingWO?.title }}</strong>"?</p>
        <div class="modal__footer">
          <button class="btn-cancel" @click="showDeleteModal = false">Cancelar</button>
          <button class="btn-delete" :disabled="saving" @click="deleteWO">
            {{ saving ? 'Eliminando...' : 'Eliminar' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { workOrdersService } from '../services/workorders'

// State
const workOrders = ref([])
const loading = ref(false)
const search = ref('')
const filterStatus = ref('')

// Modal
const showModal = ref(false)
const editingWO = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref(defaultForm())

// Delete
const showDeleteModal = ref(false)
const deletingWO = ref(null)

function defaultForm() {
  return { title: '', description: '', status: 'open', priority: 'medium' }
}

// Computed
const filtered = computed(() => {
  return workOrders.value.filter(wo => {
    const matchSearch = !search.value ||
      wo.title?.toLowerCase().includes(search.value.toLowerCase()) ||
      wo.description?.toLowerCase().includes(search.value.toLowerCase())
    const matchStatus = !filterStatus.value || wo.status === filterStatus.value
    return matchSearch && matchStatus
  })
})

// Lifecycle
onMounted(fetchWOs)

// Methods
async function fetchWOs() {
  loading.value = true
  try {
    const res = await workOrdersService.getAll()
    workOrders.value = res.data
  } catch (e) {
    console.error('Error cargando WOs', e)
  } finally {
    loading.value = false
  }
}

function openModal(wo = null) {
  editingWO.value = wo
  form.value = wo
    ? { title: wo.title, description: wo.description || '', status: wo.status, priority: wo.priority }
    : defaultForm()
  formError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingWO.value = null
}

async function saveWO() {
  formError.value = ''
  saving.value = true
  try {
    if (editingWO.value) {
      const res = await workOrdersService.update(editingWO.value.id, form.value)
      const idx = workOrders.value.findIndex(w => w.id === editingWO.value.id)
      workOrders.value[idx] = res.data
    } else {
      const res = await workOrdersService.create(form.value)
      workOrders.value.unshift(res.data)
    }
    closeModal()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

function confirmDelete(wo) {
  deletingWO.value = wo
  showDeleteModal.value = true
}

async function deleteWO() {
  saving.value = true
  try {
    await workOrdersService.delete(deletingWO.value.id)
    workOrders.value = workOrders.value.filter(w => w.id !== deletingWO.value.id)
    showDeleteModal.value = false
  } catch (e) {
    console.error('Error eliminando WO', e)
  } finally {
    saving.value = false
  }
}

// Helpers
function statusLabel(s) {
  return { new: 'Abierta', open: 'Abierta', in_progress: 'En progreso', closed: 'Cerrada', cancelled: 'Cancelada' }[s] || s
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
@import '../assets/css/workorders.css';
</style>
