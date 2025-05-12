import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src') // Добавляем алиас
    }
  },
  
  build: {
    chunkSizeWarningLimit: 1000, 
    rollupOptions: {
      external: [] 
    }
  }
})
module.exports = {
  devServer: {
    host: '0.0.0.0',
    port: 8080,
    hot: true,
    watchOptions: {
      poll: 1000 
    }
  }
}