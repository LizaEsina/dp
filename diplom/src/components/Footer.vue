<template>
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-section">
            <h3>WebSec Trainer</h3>
            <p>Практические уроки по безопасности веб-приложений.</p>
          </div>
          <div class="footer-section">
            <h4>Навигация</h4>
            <router-link to="/">Главная</router-link>
            <router-link to="/lessons">Уроки</router-link>
            <router-link to="/profile">Профиль</router-link>
          </div>
          <div class="footer-section">
            <h4>Контакты</h4>
            <a href="mailto:contact@websec-trainer.ru">contact@websec-trainer.ru</a>
            <div class="social-links">
              <a href="#"><i class="fab fa-github"></i></a>
              <a href="#"><i class="fab fa-telegram"></i></a>
              <a href="#"><i class="fab fa-vk"></i></a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2024 WebSec Trainer. Все права защищены.</p>
        </div>
      </div>
    </footer>
  </template>
  
  <script>
  export default {
    name: 'Footer'
  };
  </script>
  
  <style scoped lang="scss">
  .footer {
    background: #2c3e50;
    color: white;
    padding: 3rem 0 1rem;
    margin-top: auto;
    a {
      color: #ddd;
      text-decoration: none;
      transition: color 0.3s;
      &:hover {
        color: #42b983;
      }
    }
  }
  
  .container {
    max-width: none;
    width: 100%;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  .footer-content {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-bottom: 2rem;
  }
  
  .footer-section {
    h3, h4 {
      margin-bottom: 1rem;
      color: #42b983;
    }
    p {
      margin: 0.5rem 0;
    }
    a {
      display: block;
      margin: 0.5rem 0;
    }
  }
  
  .social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    a {
      font-size: 1.25rem;
    }
  }
  
  .footer-bottom {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #3e5163;
  }
  
  @media (max-width: 768px) {
    .footer-content {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }
  </style>