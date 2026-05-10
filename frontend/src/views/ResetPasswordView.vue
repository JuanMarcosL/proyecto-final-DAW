<template>
  <div class="login-wrapper">
    <div class="login-box">
      <h1>Field Service</h1>
      <p class="subtitle">Crea tu contraseña</p>

      <div v-if="success" class="success-msg">
        <p>✅ Contraseña creada correctamente.</p>
        <router-link to="/login" class="btn-login">Ir al login</router-link>
      </div>

      <form v-else @submit.prevent="handleReset">
        <div class="field">
          <label>Email</label>
          <input v-model="email" type="email" required readonly style="background: #f0f2f5;" />
        </div>
        <div class="field">
          <label>Nueva contraseña</label>
          <input v-model="password" type="password" placeholder="Mínimo 6 caracteres" required />
        </div>
        <div class="field">
          <label>Confirmar contraseña</label>
          <input v-model="password_confirmation" type="password" placeholder="Repite la contraseña" required />
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Guardando...' : 'Crear contraseña' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import '../assets/css/login.css'
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()

const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const error = ref('')
const loading = ref(false)
const success = ref(false)
const token = ref('')

onMounted(() => {
  token.value = route.query.token || ''
  email.value = route.query.email || ''
})

async function handleReset() {
  error.value = ''

  if (password.value !== password_confirmation.value) {
    error.value = 'Las contraseñas no coinciden.'
    return
  }

  loading.value = true
  try {
    await api.post('/reset-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'El enlace no es válido o ha expirado.'
  } finally {
    loading.value = false
  }
}
</script>
