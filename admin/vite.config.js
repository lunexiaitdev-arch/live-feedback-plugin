import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    outDir: path.resolve(__dirname, '../assets/dist'),
    emptyOutDir: true,
    manifest: 'manifest.json',
    rollupOptions: {
      input: path.resolve(__dirname, './src/admin/main.jsx'),
    },
  },
  server: {
    proxy: {
      '/wp-json': {
        target: process.env.WORDPRESS_URL || 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
