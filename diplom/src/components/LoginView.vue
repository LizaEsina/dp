<template>
  <div class="login">
    <div class="auth-form">
      <h2>Вход</h2>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Логин</label>
          <input v-model="form.login" type="text" required />
        </div>
        <div class="form-group">
          <label>Пароль</label>
          <input v-model="form.password" type="password" required />
        </div>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Загрузка...' : 'Войти' }}
        </button>
        <div v-if="error" class="error-message">{{ error }}</div>
      </form>
      <p>Нет аккаунта? <router-link to="">Зарегистрироваться</router-link></p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      form: {
        login: '',  
        password: ''
      },
      loading: false,
      error: ''
    };
  },
  methods: {
    async handleLogin() {
      this.loading = true;
      this.error = '';
      
      try {
        const response = await axios.post('http://localhost:9000/api/auth/login', this.form);
        
        if (response.data.success) {
          localStorage.setItem('auth_token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          
          this.$store.commit('setUser', response.data.user);
          this.$router.push('/');
        }
      } catch (err) {
        this.error = err.response?.data?.error || 'Ошибка сервера';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>


<style scoped>
.login {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.auth-form {
  width: 100%;
  max-width: 400px;
  background: #fff;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  width: 100%;
  padding: 0.75rem;
  background: #42b983;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background: #cccccc;
}

.error-message {
  color: #ff4444;
  margin-top: 1rem;
}
</style>