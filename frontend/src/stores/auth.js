import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  async function login(email, password) {
    const res = await api.post('/login', { email, password })
    token.value = res.data.token
    user.value = res.data.user
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const res = await api.get('/me')
      user.value = res.data
      localStorage.setItem('user', JSON.stringify(res.data))
    } catch (e) {
      logout()
    }
  }

  const isAuthenticated = () => !!token.value
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isSupervisor = computed(() => user.value?.role === 'supervisor')
  const isTecnico = computed(() => user.value?.role === 'tecnico')
  const canDispatch = computed(() => ['admin', 'supervisor'].includes(user.value?.role))

  // Recuperar usuario del localStorage al recargar
  if (!user.value && localStorage.getItem('user')) {
    user.value = JSON.parse(localStorage.getItem('user'))
  }

  return {
    user,
    token,
    login,
    logout,
    fetchMe,
    isAuthenticated,
    isAdmin,
    isSupervisor,
    isTecnico,
    canDispatch,
  }
})
