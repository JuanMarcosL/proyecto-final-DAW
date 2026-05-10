<template>
  <div class="login-wrapper">
     <div class="login-logo">
      <span class="login-logo__icon">⚡</span>
      <span class="login-logo__text">Field Service</span>
    </div>

    <div class="login-box">
      <!-- Login form -->
      <template v-if="mode === 'login'">
        <p class="subtitle">Inicia sesión para continuar</p>
        <form @submit.prevent="handleLogin">
          <div class="field">
            <label>Email</label>
            <input v-model="email" type="email" placeholder="admin@fieldservice.com" required />
          </div>
          <div class="field password-field">
            <label>Contraseña</label>
            <div class="password-wrap">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required />
              <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </button>
            </div>
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
const showPassword = ref(false)
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
