import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import WorkOrdersView from '../views/WorkOrdersView.vue'
import WorkOrderDetailView from '../views/WorkOrderDetailView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/',
      component: HomeView,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../views/DashboardView.vue'),
        },
        {
          path: 'work-orders',
          name: 'work-orders',
          component: WorkOrdersView,
        },
        {
          path: 'work-orders/:id',
          name: 'work-order-detail',
          component: WorkOrderDetailView,
        },
        {
          path: 'resources',
          name: 'resources',
          component: () => import('../views/ResourcesView.vue'),
        },
        {
          path: 'absences',
          name: 'absences',
          component: () => import('../views/AbsencesView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated()) {
    next('/login')
  } else {
    next()
  }
})

export default router
