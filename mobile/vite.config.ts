import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => ({
  base: mode === 'pwa' ? '/mobile/' : '/',
  plugins: [vue()],
  build: { outDir: mode === 'pwa' ? 'dist-pwa' : 'dist' }
}));
