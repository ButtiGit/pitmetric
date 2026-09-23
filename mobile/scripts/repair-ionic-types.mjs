import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const declarationPath = resolve('node_modules/@ionic/vue/dist/types/components/IonIcon.d.ts');

if (!existsSync(declarationPath)) {
  mkdirSync(dirname(declarationPath), { recursive: true });
  writeFileSync(
    declarationPath,
    `import type { DefineComponent, PropType } from 'vue';\n\nexport declare const IonIcon: DefineComponent<{\n  color: { type: PropType<string> };\n  flipRtl: { type: PropType<boolean> };\n  icon: { type: PropType<string> };\n  ios: { type: PropType<string> };\n  lazy: { type: PropType<boolean> };\n  md: { type: PropType<string> };\n  mode: { type: PropType<string> };\n  name: { type: PropType<string> };\n  size: { type: PropType<string> };\n  src: { type: PropType<string> };\n}>;\n`,
    'utf8',
  );
  console.log('Repaired missing @ionic/vue IonIcon type declaration.');
} else {
  console.log('@ionic/vue IonIcon type declaration already present; no repair needed.');
}
