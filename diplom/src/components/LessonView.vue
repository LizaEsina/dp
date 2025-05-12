<template>
    <div class="lesson">
      <div class="lesson-content">
        <h1>{{ lesson.title }}</h1>
        <div class="theory" v-html="lesson.content"></div>
      </div>
  
      <div class="sandbox">
        <h2>Практика</h2>
        <div class="code-tabs">
          <button 
            @click="activeTab = 'vulnerable'"
            :class="{ active: activeTab === 'vulnerable' }"
          >
            Уязвимый код
          </button>
          <button 
            @click="activeTab = 'secure'"
            :class="{ active: activeTab === 'secure' }"
          >
            Защищенный код
          </button>
        </div>
  
        <div class="code-block">
          <pre v-if="activeTab === 'vulnerable'">{{ lesson.vulnerableCode }}</pre>
          <pre v-else>{{ lesson.secureCode }}</pre>
        </div>
  
        <button class="hack-button" @click="tryHack">
          {{ activeTab === 'vulnerable' ? 'Взломать!' : 'Проверить защиту' }}
        </button>
  
        <div v-if="hackResult" class="result" :class="{ success: hackResult.success }">
          {{ hackResult.message }}
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        activeTab: "vulnerable",
        hackResult: null,
        lesson: {
          id: 1,
          title: "SQL-инъекции",
          content: "<h2>Что такое SQL-инъекция?</h2><p>Это атака, при которой злоумышленник внедряет вредоносный SQL-код...</p>",
          vulnerableCode: "SELECT * FROM users WHERE id = " + userInput + ";",
          secureCode: "SELECT * FROM users WHERE id = ?;",
        },
      };
    },
    methods: {
      tryHack() {
        if (this.activeTab === "vulnerable") {
          this.hackResult = {
            success: false,
            message: "Уязвимость успешно эксплуатирована! Данные пользователей утекли.",
          };
        } else {
          this.hackResult = {
            success: true,
            message: "Защита сработала! Инъекция невозможна.",
          };
        }
      },
    },
  };
  </script>
  
  <style scoped>
  .lesson {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }
  
  .theory {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .sandbox {
    background: #f9f9f9;
    padding: 1.5rem;
    border-radius: 8px;
  }
  
  .code-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }
  
  .code-tabs button {
    padding: 0.5rem 1rem;
    border: none;
    background: #ddd;
    cursor: pointer;
    border-radius: 4px 4px 0 0;
  }
  
  .code-tabs button.active {
    background: #4caf50;
    color: white;
  }
  
  .code-block {
    background: #282c34;
    color: #abb2bf;
    padding: 1rem;
    border-radius: 0 0 4px 4px;
    font-family: monospace;
    overflow-x: auto;
  }
  
  .hack-button {
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: #f44336;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.2s;
  }
  
  .hack-button:hover {
    background: #d32f2f;
  }
  
  .result {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 4px;
  }
  
  .result.success {
    background: #4caf50;
    color: white;
  }
  
  .result:not(.success) {
    background: #f44336;
    color: white;
  }
  </style>