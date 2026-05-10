<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="logo">
        <span class="logo-icon">⚡</span>
        <div>
          <div class="logo-text">Field Service</div>
        </div>
      </div>

      <nav class="nav">
        <router-link v-if="auth.user?.role !== 'tecnico'" to="/" class="nav-item">
          <span class="nav-icon">▦</span>
          <span>Dashboard</span>
        </router-link>
        <router-link to="/work-orders" class="nav-item">
          <span class="nav-icon">📋</span>
          <span>Órdenes de trabajo</span>
        </router-link>
        <router-link to="/calendar" class="nav-item">
          <span class="nav-icon">📅</span>
          <span>Calendario</span>
        </router-link>
        <router-link v-if="auth.user?.role !== 'tecnico'" to="/resources" class="nav-item">
          <span class="nav-icon">👷</span>
          <span>Técnicos</span>
        </router-link>
        <router-link to="/absences" class="nav-item">
          <span class="nav-icon">🏖️</span>
          <span>Ausencias</span>
        </router-link>
        <router-link v-if="auth.user?.role !== 'tecnico'" to="/reports" class="nav-item">
          <span class="nav-icon">📊</span>
          <span>Reportes</span>
        </router-link>
        <router-link v-if="auth.user?.role !== 'tecnico'" to="/users" class="nav-item">
          <span class="nav-icon">👥</span>
          <span>Usuarios</span>
        </router-link>
      </nav>

      <div class="sidebar-user">
        <div class="user-avatar">{{ userInitials }}</div>
        <div>
          <div class="user-name">{{ auth.user?.name }}</div>
          <div class="user-role">{{ roleLabel(auth.user?.role) }}</div>
        </div>
        <button class="logout-btn" @click="handleLogout">↩</button>
      </div>
    </aside>

    <div class="main">
      <div class="content">
        <RouterView />
      </div>
    </div>
  </div>
</template>

<script setup>
import '../assets/css/layout.css'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const userInitials = computed(() => {
  return auth.user?.name?.split(' ').map(n => n[0]).join('').toUpperCase() || 'U'
})

async function handleLogout() {
  auth.logout()
  router.push('/login')
}

function roleLabel(role) {
  return { admin: 'Administrador', supervisor: 'Supervisor', tecnico: 'Técnico' }[role] || role
}
</script>
