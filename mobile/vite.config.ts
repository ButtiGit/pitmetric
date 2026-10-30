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
      const publicAssets = ['manifest.webmanifest', 'icons/pitmetric-180.png', 'icons/pitmetric-192.png', 'icons/pitmetric-512.png', 'brand/pitmetric-mark-light.svg', 'brand/pitmetric-primary-light.svg'];
      const assets = [...Object.keys(bundle).filter(name => /\.(js|css)$/.test(name)), ...publicAssets].map(name => `/mobile/${name}`);
      const hash = createHash('sha256').update(assets.join('\n'));
      for (const asset of publicAssets) hash.update(readFileSync(new URL(`./public/${asset}`, import.meta.url)));
      const version = hash.digest('hex').slice(0, 16);
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

