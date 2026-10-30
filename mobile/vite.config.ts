import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';

export default defineConfig(({ mode }) => ({
  base: mode === 'pwa' ? '/mobile/' : '/',
  plugins: [vue(), {
    name: 'pitmetric-offline-shell',
    generateBundle(_options, bundle) {
      if (mode !== 'pwa') return;
      const assets = Object.keys(bundle).filter(name => /\.(js|css)$/.test(name)).map(name => `/mobile/${name}`);
      const version = createHash('sha256').update(assets.join('\n')).digest('hex').slice(0, 16);
      const worker = readFileSync(new URL('./public/sw.js', import.meta.url), 'utf8')
        .replace("'__PWA_ASSETS__'", JSON.stringify(assets))
        .replace("'__PWA_VERSION__'", JSON.stringify(version));
      this.emitFile({ type: 'asset', fileName: 'sw.js', source: worker });
    },
  }],
  build: {
    outDir: mode === 'pwa' ? '../public/mobile' : 'dist',
    emptyOutDir: true
  }
}));

