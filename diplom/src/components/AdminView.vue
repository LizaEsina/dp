<template>
    <div class="admin">
      <h1>Админ-панель</h1>
      <div class="admin-tabs">
        <button @click="activeTab = 'lessons'" :class="{ active: activeTab === 'lessons' }">
          Уроки
        </button>
        <button @click="activeTab = 'users'" :class="{ active: activeTab === 'users' }">
          Пользователи
        </button>
      </div>
  
      <div v-if="activeTab === 'lessons'" class="lessons-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Тип</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lesson in lessons" :key="lesson.id">
              <td>{{ lesson.id }}</td>
              <td>{{ lesson.title }}</td>
              <td>{{ lesson.type }}</td>
              <td>
                <button @click="editLesson(lesson.id)">✏️</button>
                <button @click="deleteLesson(lesson.id)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
        <button @click="addLesson">Добавить урок</button>
      </div>
  
      <div v-else class="users-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Email</th>
              <th>Роль</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.role }}</td>
              <td>
                <button @click="editUser(user.id)">✏️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        activeTab: "lessons",
        lessons: [
          { id: 1, title: "SQL-инъекции", type: "sqli" },
          { id: 2, title: "XSS", type: "xss" },
        ],
        users: [
          { id: 1, email: "admin@test.com", role: "admin" },
          { id: 2, email: "user@test.com", role: "user" },
        ],
      };
    },
    methods: {
      editLesson(id) {
        alert(`Редактировать урок ${id}`);
      },
      deleteLesson(id) {
        if (confirm("Удалить урок?")) {
          this.lessons = this.lessons.filter((lesson) => lesson.id !== id);
        }
      },
      addLesson() {
        alert("Добавить новый урок");
      },
      editUser(id) {
        alert(`Редактировать пользователя ${id}`);
      },
    },
  };
  </script>
  
  <style scoped>
  .admin {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }
  
  .admin-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }
  
  .admin-tabs button {
    padding: 0.5rem 1rem;
    border: none;
    background: #ddd;
    cursor: pointer;
  }
  
  .admin-tabs button.active {
    background: #4caf50;
    color: white;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
  }
  
  th, td {
    padding: 0.75rem;
    border: 1px solid #ddd;
    text-align: left;
  }
  
  th {
    background: #f5f5f5;
  }
  
  button {
    margin-right: 0.5rem;
    cursor: pointer;
  }
  </style>