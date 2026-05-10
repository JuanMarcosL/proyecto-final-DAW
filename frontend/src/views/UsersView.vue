<template>
  <div class="users-page">

    <!-- Header -->
    <div class="users-header">
      <div class="users-header__left">
        <h1 class="users-title">Usuarios</h1>
        <span class="users-count">{{ users.length }} registros</span>
      </div>
      <button class="btn-new" @click="openModal()">+ Nuevo usuario</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="users-loading">
      <div class="spinner"></div>
    </div>

    <!-- Table -->
    <div v-else class="users-table-wrap">
      <table class="users-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Creado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="users-row">
            <td class="users-id">{{ u.id }}</td>
            <td class="users-name">
              <div class="users-avatar">{{ initials(u.name) }}</div>
              <span>{{ u.name }}</span>
            </td>
            <td>{{ u.email }}</td>
            <td><span :class="['badge-role', `badge-role--${u.role}`]">{{ roleLabel(u.role) }}</span></td>
            <td>
              <span :class="['badge-active', u.active ? 'badge-active--on' : 'badge-active--off']">
                {{ u.active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="users-date">{{ formatDate(u.created_at) }}</td>
            <td class="users-actions">
              <button v-if="canEdit(u)" class="btn-icon" @click="openModal(u)" title="Editar">✏️</button>
              <button v-if="isAdmin" class="btn-icon btn-icon--delete" @click="confirmDelete(u)" title="Eliminar"
                :disabled="u.id === auth.user?.id">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editingUser ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
          <button class="modal__close" @click="showModal = false">×</button>
        </div>
        <form class="modal__form" @submit.prevent="saveUser">
          <div class="form-group">
            <label>Nombre *</label>
            <input v-model="form.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="form.email" type="email" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Rol</label>
              <select v-model="form.role" required>
                <option v-if="isAdmin" value="admin">Administrador</option>
                <option v-if="isAdmin" value="supervisor">Supervisor</option>
                <option value="tecnico">Técnico</option>
              </select>
            </div>
            <div class="form-group">
              <label>Estado</label>
              <select v-model="form.active">
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
              </select>
            </div>
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
          <h2>Eliminar usuario</h2>
          <button class="modal__close" @click="showDeleteModal = false">×</button>
        </div>
        <p class="delete-msg">¿Seguro que quieres eliminar a <strong>{{ deletingUser?.name }}</strong>? Esta acción no
          se puede deshacer.</p>
        <div class="modal__footer">
          <button class="btn-cancel" @click="showDeleteModal = false">Cancelar</button>
          <button class="btn-delete" :disabled="saving" @click="deleteUser">{{ saving ? 'Eliminando...' : 'Eliminar'
            }}</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const users = ref([])
const loading = ref(false)

const showModal = ref(false)
const editingUser = ref(null)
const form = ref(defaultForm())
const formError = ref('')
const saving = ref(false)

const showDeleteModal = ref(false)
const deletingUser = ref(null)

const isAdmin = computed(() => auth.user?.role === 'admin')

function canEdit(u) {
  if (auth.user?.role === 'admin') return true
  if (auth.user?.role === 'supervisor' && u.role === 'tecnico') return true
  return false
}

onMounted(async () => {
  await auth.fetchMe()
  await fetchUsers()
})

function defaultForm() {
  return { name: '', email: '', password: '', role: 'tecnico', active: true }
}

async function fetchUsers() {
  loading.value = true
  try {
    const res = await api.get('/users')
    users.value = res.data
  } catch (e) {
    console.error('Error cargando usuarios', e)
  } finally {
    loading.value = false
  }
}

function openModal(u = null) {
  editingUser.value = u
  form.value = u
    ? { name: u.name, email: u.email, password: '', role: u.role, active: u.active }
    : defaultForm()
  formError.value = ''
  showModal.value = true
}

async function saveUser() {
  saving.value = true
  formError.value = ''
  try {
    if (editingUser.value) {
      const payload = { ...form.value }
      if (!payload.password) delete payload.password
      const res = await api.put(`/users/${editingUser.value.id}`, payload)
      const idx = users.value.findIndex(u => u.id === editingUser.value.id)
      users.value[idx] = res.data
    } else {
      const res = await api.post('/users', form.value)
      users.value.unshift(res.data)
    }
    showModal.value = false
  } catch (e) {
    formError.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    saving.value = false
  }
}

function confirmDelete(u) {
  deletingUser.value = u
  showDeleteModal.value = true
}

async function deleteUser() {
  saving.value = true
  try {
    await api.delete(`/users/${deletingUser.value.id}`)
    users.value = users.value.filter(u => u.id !== deletingUser.value.id)
    showDeleteModal.value = false
  } catch (e) {
    console.error('Error eliminando usuario', e)
  } finally {
    saving.value = false
  }
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function roleLabel(role) {
  return { admin: 'Administrador', supervisor: 'Supervisor', tecnico: 'Técnico' }[role] || role
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<style scoped>
@import '../assets/css/users.css';
</style>
