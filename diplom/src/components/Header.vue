<template>
    <header class="header">
      <div class="container">
        <!-- Логотип -->
        <router-link to="/" class="logo">
          <span>WebSec</span> Trainer
        </router-link>
  
        <!-- Основное меню (видно на десктопе) -->
        <nav class="desktop-nav">
          <router-link to="/lessons">Уроки</router-link>
          <router-link to="/profile">Профиль</router-link>
          <router-link v-if="isAdmin" to="/admin">Админ</router-link>
        </nav>
  
        <!-- Кнопки авторизации (десктоп) -->
        <div class="desktop-auth">
          <router-link v-if="!user" to="/login" class="login-btn">Войти</router-link>
          <div v-else class="user-dropdown">
            <div class="dropdown-content">
              <router-link to="/profile">Профиль</router-link>
              <button @click="logout">Выйти</button>
            </div>
          </div>
        </div>
  
        <!-- Гамбургер-меню (только для мобильных) -->
        <button 
          class="mobile-menu-toggle"
          @click="toggleMenu"
          :class="{ 'active': isMobileMenuOpen }"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
  
      <!-- Мобильное меню (раскрывается по клику) -->
      <div class="mobile-nav" :class="{ 'active': isMobileMenuOpen }">
        <router-link to="/lessons" @click="toggleMenu">Уроки</router-link>
        <router-link to="/profile" @click="toggleMenu">Профиль</router-link>
        <router-link v-if="isAdmin" to="/admin" @click="toggleMenu">Админ</router-link>
        <div class="mobile-auth">
          <router-link v-if="!user" to="/login" @click="toggleMenu" class="login-btn">Войти</router-link>
          <button v-else @click="logout" class="logout-btn">Выйти</button>
        </div>
      </div>
    </header>
  </template>
  
  <script>
  import { ref, onMounted, onUnmounted } from 'vue';
  import { useRouter } from 'vue-router';
  
  export default {
    setup() {
      const router = useRouter();
      const isMobileMenuOpen = ref(false);
      const user = ref(null); // Здесь должна быть логика получения пользователя
      const isAdmin = ref(false);
  
      const toggleMenu = () => {
        isMobileMenuOpen.value = !isMobileMenuOpen.value;
      };
  
      const logout = () => {
        // Логика выхода
        router.push('/login');
      };
  
      // Закрываем меню при ресайзе (если вдруг пользователь увеличил окно)
      const handleResize = () => {
        if (window.innerWidth > 768) {
          isMobileMenuOpen.value = false;
        }
      };
  
      onMounted(() => window.addEventListener('resize', handleResize));
      onUnmounted(() => window.removeEventListener('resize', handleResize));
  
      return { isMobileMenuOpen, toggleMenu, user, isAdmin, logout };
    }
  };
  </script>
  
  <style scoped lang="scss">
  .header {
    background: #2c3e50;
    color: white;
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  
  .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: none;
    width: 100%;
    margin: 0 auto;
    padding: 0 2rem;
  }
  
  .logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
    span {
      color: #42b983;
    }
  }
  
  /* Десктопное меню */
  .desktop-nav {
    display: flex;
    gap: 2rem;
    a {
      color: white;
      text-decoration: none;
      transition: all 0.3s;
      padding: 0.5rem 0;
      position: relative;
      &:hover {
        color: #42b983;
      }
      &.router-link-active {
        &::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 2px;
          background: #42b983;
        }
      }
    }
  }
  
  .desktop-auth {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  
  .login-btn {
    background: #42b983;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.3s;
    &:hover {
      background: darken(#42b983, 10%);
    }
  }
  
  .user-dropdown {
    position: relative;
    cursor: pointer;
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #42b983;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      background: white;
      min-width: 160px;
      border-radius: 4px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
      z-index: 1;
      a, button {
        display: block;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        text-align: left;
        width: 100%;
        background: none;
        border: none;
        cursor: pointer;
        &:hover {
          background: #f5f5f5;
        }
      }
    }
    &:hover .dropdown-content {
      display: block;
    }
  }
  
  /* Мобильное меню (скрыто на десктопе) */
  .mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    span {
      display: block;
      width: 25px;
      height: 2px;
      background: white;
      margin: 5px 0;
      transition: all 0.3s;
    }
    &.active span:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }
    &.active span:nth-child(2) {
      opacity: 0;
    }
    &.active span:nth-child(3) {
      transform: rotate(-45deg) translate(5px, -5px);
    }
  }
  
  .mobile-nav {
    display: none;
    background: #2c3e50;
    padding: 1rem 2rem;
    flex-direction: column;
    gap: 1rem;
    a {
      color: white;
      text-decoration: none;
      padding: 0.5rem 0;
      border-bottom: 1px solid #3e5163;
    }
    .mobile-auth {
      margin-top: 1rem;
      .login-btn, .logout-btn {
        width: 100%;
        text-align: center;
      }
      .logout-btn {
        background: #e74c3c;
        color: white;
        padding: 0.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
      }
    }
    &.active {
      display: flex;
    }
  }
  
  /* Адаптивность */
  @media (min-width: 1600px) {
  .container {
    max-width: 1500px; /* Или другой подходящий размер */
    margin: 0 auto;
  }
}
  @media (max-width: 768px) {
    .desktop-nav, .desktop-auth {
      display: none;
    }
    .mobile-menu-toggle {
      display: block;
    }
  }
  </style>