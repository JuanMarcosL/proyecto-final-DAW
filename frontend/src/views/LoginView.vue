<template>
  <div class="login-wrapper">
    <div class="login-box">
      <h1>Field Service</h1>

      <!-- Login form -->
      <template v-if="mode === 'login'">
        <p class="subtitle">Inicia sesión para continuar</p>
        <form @submit.prevent="handleLogin">
          <div class="field">
            <label>Email</label>
            <input v-model="email" type="email" placeholder="admin@fieldservice.com" required />
          </div>
          <div class="field">
            <label>Contraseña</label>
            <input v-model="password" type="password" placeholder="••••••••" required />
          </div>
          <p v-if="error" class="error">{{ error }}</p>
          <button type="submit" :disabled="loading">
            {{ loading ? 'Entrando...' : 'Iniciar sesión' }}
          </button>
        </form>
        <p class="forgot-link">
          <a href="#" @click.prevent="mode = 'forgot'">¿Olvidaste tu contraseña?</a>
        </p>
      </template>

      <!-- Forgot password form -->
      <template v-if="mode === 'forgot'">
        <p class="subtitle">Recuperar contraseña</p>

        <div v-if="forgotSuccess" class="success-msg">
          <p>✅ Si el email existe, recibirás un enlace en tu bandeja.</p>
          <a href="#" @click.prevent="mode = 'login'" class="back-link">← Volver al login</a>
        </div>

        <form v-else @submit.prevent="handleForgot">
          <div class="field">
            <label>Email</label>
            <input v-model="forgotEmail" type="email" placeholder="tu@email.com" required />
          </div>
          <p v-if="forgotError" class="error">{{ forgotError }}</p>
          <button type="submit" :disabled="forgotLoading">
            {{ forgotLoading ? 'Enviando...' : 'Enviar enlace' }}
          </button>
        </form>
        <p class="forgot-link" v-if="!forgotSuccess">
          <a href="#" @click.prevent="mode = 'login'">← Volver al login</a>
        </p>
      </template>

    </div>
  </div>
</template>

<script setup>
import '../assets/css/login.css'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'

const router = useRouter()
const auth = useAuthStore()

const mode = ref('login')

// Login
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Forgot
const forgotEmail = ref('')
const forgotError = ref('')
const forgotLoading = ref(false)
const forgotSuccess = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    if (auth.user?.role === 'tecnico') {
      router.push('/work-orders')
    } else {
      router.push('/')
    }
  } catch (e) {
    error.value = 'Email o contraseña incorrectos.'
  } finally {
    loading.value = false
  }
}

async function handleForgot() {
  forgotError.value = ''
  forgotLoading.value = true
  try {
    await api.post('/forgot-password', { email: forgotEmail.value })
    forgotSuccess.value = true
  } catch (e) {
    forgotError.value = e.response?.data?.message || 'Error al enviar el email.'
  } finally {
    forgotLoading.value = false
  }
}
</script>
