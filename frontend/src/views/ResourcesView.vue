<template>
  <div class="resources-page">

    <!-- Header -->
    <div class="resources-header">
      <div class="resources-header__left">
        <h1 class="resources-title">Técnicos</h1>
        <span class="resources-count">{{ resources.length }} registros</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="res-loading">
      <div class="spinner"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="resources.length === 0" class="res-empty">
      <div class="res-empty__icon">👷</div>
      <p>No hay técnicos registrados</p>
    </div>

    <!-- Grid -->
    <div v-else class="resources-grid">
      <div v-for="r in resources" :key="r.id" class="resource-card">
        <div class="resource-card__header">
          <div class="resource-avatar">{{ initials(r.user?.name) }}</div>
          <div class="resource-info">
            <span class="resource-name">{{ r.user?.name }}</span>
            <span class="resource-email">{{ r.user?.email }}</span>
          </div>
          <span :class="['resource-status', r.active ? 'resource-status--active' : 'resource-status--inactive']">
            {{ r.active ? 'Activo' : 'Inactivo' }}
          </span>
        </div>

        <div class="resource-details">
          <div class="resource-detail">
            <span class="resource-detail__label">Especialidad</span>
            <span class="resource-detail__value">{{ r.specialty }}</span>
          </div>
          <div class="resource-detail">
            <span class="resource-detail__label">Zona</span>
            <span class="resource-detail__value">{{ r.zone }}</span>
          </div>
          <div class="resource-detail">
            <span class="resource-detail__label">Rol</span>
            <span class="resource-detail__value">{{ roleLabel(r.user?.role) }}</span>
          </div>
          <div class="resource-detail">
            <span class="resource-detail__label">Citas asignadas</span>
            <span class="resource-detail__value">{{ r.appointments?.length || 0 }}</span>
          </div>
        </div>

        <div class="resource-card__footer" v-if="canEdit">
          <button class="btn-edit-res" @click="openEditModal(r)">✏️ Editar</button>
          <button class="btn-toggle" :class="r.active ? 'btn-toggle--deactivate' : 'btn-toggle--activate'"
            @click="toggleActive(r)">
            {{ r.active ? 'Desactivar' : 'Activar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Editar -->
    <div v-if="showEditModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>Editar Técnico</h2>
          <button class="modal__close" @click="showEditModal = false">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveResource">
          <div class="form-group">
            <label>Nombre</label>
            <input :value="editingResource?.user?.name" type="text" readonly
              style="background: var(--color-surface2); cursor: not-allowed;" />
          </div>
          <div class="form-group">
            <label>Especialidad</label>
            <input v-model="editForm.specialty" type="text" required />
          </div>
          <div class="form-group">
            <label>Zona</label>
            <input v-model="editForm.zone" type="text" required />
          </div>
          <div class="form-group">
            <label>Estado</label>
            <select v-model="editForm.active">
              <option :value="true">Activo</option>
              <option :value="false">Inactivo</option>
            </select>
          </div>
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="modal__footer">
            <button type="button" class="btn-cancel" @click="showEditModal = false">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { resourcesService } from '../services/resources'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const resources = ref([])
const loading = ref(false)

const showEditModal = ref(false)
const editingResource = ref(null)
const editForm = ref({})
const formError = ref('')
const saving = ref(false)

const canEdit = computed(() => ['admin', 'supervisor'].includes(auth.user?.role))

onMounted(async () => {
  await auth.fetchMe()
  await fetchResources()
})

async function fetchResources() {
  loading.value = true
  try {
    const res = await resourcesService.getAll()
    resources.value = res.data
  } catch (e) {
    console.error('Error cargando técnicos', e)
  } finally {
    loading.value = false
  }
}

function openEditModal(r) {
  editingResource.value = r
  editForm.value = {
    specialty: r.specialty,
    zone: r.zone,
    active: r.active,
  }
  formError.value = ''
  showEditModal.value = true
}

async function saveResource() {
  saving.value = true
  formError.value = ''
  try {
    const res = await resourcesService.update(editingResource.value.id, editForm.value)
    const idx = resources.value.findIndex(r => r.id === editingResource.value.id)
    resources.value[idx] = { ...resources.value[idx], ...res.data }
    showEditModal.value = false
  } catch (e) {
    formError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

async function toggleActive(r) {
  try {
    const res = await resourcesService.update(r.id, { active: !r.active })
    const idx = resources.value.findIndex(x => x.id === r.id)
    resources.value[idx] = { ...resources.value[idx], active: res.data.active }
  } catch (e) {
    console.error('Error actualizando estado', e)
  }
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function roleLabel(role) {
  return { admin: 'Administrador', supervisor: 'Supervisor', tecnico: 'Técnico' }[role] || role
}
</script>

<style scoped>
@import '../assets/css/resources.css';
</style>
