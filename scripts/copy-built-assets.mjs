import { copyFileSync, existsSync, mkdirSync, readFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = join(root, 'public/build/manifest.json');

if (!existsSync(manifestPath)) {
    console.error('No existe public/build/manifest.json. Ejecuta vite build primero.');
    process.exit(1);
}

const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));

function copyEntry(entry, destRel) {
    const item = manifest[entry];
    if (!item?.file) {
        console.warn('No se encontró en el manifest:', entry);
        return;
    }
    const src = join(root, 'public/build', item.file);
    const dest = join(root, 'public', destRel);
    mkdirSync(dirname(dest), { recursive: true });
    copyFileSync(src, dest);
    console.log(`Copiado ${entry} -> public/${destRel}`);
}

copyEntry('resources/css/app.css', 'css/sams.css');
copyEntry('resources/js/app.js', 'js/sams.js');
copyEntry('resources/js/charts.js', 'js/charts.js');
