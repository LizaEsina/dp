<template>
  <div class="app-wrapper">
    <Header />
    <main class="main-content">
      <router-view />
    </main>
    <Footer />
  </div>
</template>

<script>
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';

export default {
  components: { Header, Footer }
};
</script>

<style lang="scss">
/* Базовые стили */
html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100vh;
  overflow-x: hidden;
}

.app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100%;
}

.main-content {
  flex: 1;
  width: 100%;
  padding: 2rem 0;
}

/* Адаптивные контейнеры */
.container {
  width: 100%;
  max-width: 1800px; /* Можно увеличить */
  margin: 0 auto;
  padding: 0 20px;
}

@media (min-width: 2000px) {
  .container {
    max-width: 90%; /* Или фиксированное значение */
  }
}
</style>