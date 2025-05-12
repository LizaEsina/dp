<template>
    <div class="home">
      <header class="header">
        <h1>Web Security Trainer</h1>
        <p>Изучите уязвимости веб-приложений на практике</p>
      </header>
  
      <div class="filter-bar">
        <select v-model="filterType">
          <option value="all">Все темы</option>
          <option value="sqli">SQL-инъекции</option>
          <option value="xss">XSS</option>
          <option value="csrf">CSRF</option>
        </select>
        <select v-model="filterDifficulty">
          <option value="all">Любая сложность</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </div>
  
      <div class="lessons-grid">
        <div 
          v-for="lesson in filteredLessons" 
          :key="lesson.id" 
          class="lesson-card"
          @click="goToLesson(lesson.id)"
        >
          <div class="lesson-badge" :class="lesson.difficulty">
            {{ lesson.difficulty }}
          </div>
          <h3>{{ lesson.title }}</h3>
          <p>{{ lesson.description }}</p>
          <div class="progress" v-if="lesson.progress">
            <div class="progress-bar" :style="{ width: lesson.progress + '%' }"></div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        filterType: "all",
        filterDifficulty: "all",
        lessons: [
          {
            id: 1,
            title: "SQL-инъекции",
            description: "Научитесь эксплуатировать и защищаться от SQLi.",
            difficulty: "medium",
            type: "sqli",
            progress: 30,
          },
          {
            id: 2,
            title: "XSS-атаки",
            description: "Хранимый, отраженный и DOM-based XSS.",
            difficulty: "high",
            type: "xss",
            progress: 0,
          },
        ],
      };
    },
    computed: {
      filteredLessons() {
        return this.lessons.filter((lesson) => {
          const typeMatch = this.filterType === "all" || lesson.type === this.filterType;
          const difficultyMatch =
            this.filterDifficulty === "all" || lesson.difficulty === this.filterDifficulty;
          return typeMatch && difficultyMatch;
        });
      },
    },
    methods: {
      goToLesson(id) {
        this.$router.push(`/lesson/${id}`);
      },
    },
  };
  </script>
  
  <style scoped>
  .home {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }
  
  .header {
    text-align: center;
    margin-bottom: 2rem;
  }
  
  .filter-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  select {
    padding: 0.5rem;
    border-radius: 4px;
    border: 1px solid #ddd;
  }
  
  .lessons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .lesson-card {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.2s;
  }
  
  .lesson-card:hover {
    transform: translateY(-5px);
  }
  
  .lesson-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
  }
  
  .lesson-badge.low {
    background: #4caf50;
    color: white;
  }
  
  .lesson-badge.medium {
    background: #ff9800;
    color: white;
  }
  
  .lesson-badge.high {
    background: #f44336;
    color: white;
  }
  
  .progress {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    margin-top: 1rem;
  }
  
  .progress-bar {
    height: 100%;
    background: #4caf50;
    border-radius: 3px;
  }
  </style>