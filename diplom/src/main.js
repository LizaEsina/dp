import './assets/main.css'
import '@fortawesome/fontawesome-free/js/all.js';
import { createApp } from 'vue';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';
import AdminView from './components/AdminView.vue'
import HomeView from './components/HomeView.vue'
import LessonView from './components/LessonView.vue'
import LoginView from './components/LoginView.vue'
import ProfileView from './components/ProfileView.vue'
const routes = [
    { path: '/', component: HomeView },
    { path: '/lessons', component: LessonView },
    { path: '/profile', component: ProfileView },
    { path: '/admin', component: AdminView },
    { path: '/login', component: LoginView },
  ];
  const router = createRouter({
    history: createWebHistory(),
    routes,
  });
  createApp(App)
    .use(router)
    .mount('#app');
