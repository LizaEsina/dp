import { createRouter, createWebHistory } from 'vue-router';
import store from '../store';

const routes = [
  // ... другие маршруты
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  store.dispatch('initialize');
  
  if (to.meta.requiresAuth && !store.state.token) {
    next('/login');
  } else if (!to.meta.requiresAuth && store.state.token) {
    next('/');
  } else {
    next();
  }
});

export default router;