<template>
    <div class="profile">
      <h1>Личный кабинет</h1>
      <div class="stats">
        <div class="stat-card">
          <h3>Пройдено уроков</h3>
          <p>{{ completedLessons }}/{{ totalLessons }}</p>
        </div>
        <div class="stat-card">
          <h3>Уровень безопасности</h3>
          <p>{{ securityLevel }}</p>
        </div>
      </div>
      <div class="progress-chart">
        <canvas ref="chart"></canvas>
      </div>
    </div>
  </template>
  
  <script>
  import { Chart, registerables } from "chart.js";
  Chart.register(...registerables);
  
  export default {
    data() {
      return {
        completedLessons: 3,
        totalLessons: 10,
        securityLevel: "Новичок",
      };
    },
    mounted() {
      this.renderChart();
    },
    methods: {
      renderChart() {
        const ctx = this.$refs.chart.getContext("2d");
        new Chart(ctx, {
          type: "bar",
          data: {
            labels: ["SQLi", "XSS", "CSRF", "IDOR"],
            datasets: [
              {
                label: "Прогресс по темам",
                data: [75, 50, 30, 20],
                backgroundColor: ["#4caf50", "#ff9800", "#f44336", "#2196f3"],
              },
            ],
          },
        });
      },
    },
  };
  </script>
  
  <style scoped>
  .profile {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }
  
  .stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    flex: 1;
  }
  
  .progress-chart {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  </style>