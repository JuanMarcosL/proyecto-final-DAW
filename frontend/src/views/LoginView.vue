<template>
  <div class="login-wrapper">
    <div class="login-box">
      <h1>Field Service</h1>
      <p class="subtitle">Inicia sesión para continuar</p>

      <form @submit.prevent="handleLogin">
        <div class="field">
          <label>Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="admin@fieldservice.com"
            required
          />
        </div>

        <div class="field">
          <label>Contraseña</label>
          <input
            v-model="password"
            type="password"
            placeholder="••••••••"
            required
          />
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Entrando...' : 'Iniciar sesión' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import '../assets/css/login.css'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    error.value = 'Email o contraseña incorrectos.'
  } finally {
    loading.value = false
  }
}
</script>
