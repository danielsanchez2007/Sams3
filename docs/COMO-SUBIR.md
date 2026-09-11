# Cómo subir SAMS (sistema + base de datos)

El servidor **no necesita Node.js**. Los estilos ya van compilados en el código. No hay pantalla de instalación: se sube el sistema, se deja el `.env` del servidor y se abre `/inicio`.

## Cómo queda conectado

```
Navegador
   │
   ├─ /inicio  /login  /admin  →  public/index.php  →  Laravel
   │
   ├─ /build/assets/*.css|js   →  estilos Tailwind (Vite)
   ├─ /css/sams.css /js/sams.js →  copia estable por si falta el hash
   │
   └─ MySQL  ←  .env del SERVIDOR (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
                 Sesiones y caché van en storage/ (archivos)
```

1. **Archivos PHP** pintan pantallas y guardan datos.
2. **`public/build`** y **`public/css/sams.css`** son el diseño.
3. **MySQL** guarda usuarios, equipos, empresas. El `.env` del hosting es el cable.
4. **`storage/app/public`** guarda fotos y firmas.

## En tu PC (una sola vez por versión)

```bash
composer install --no-dev --optimize-autoloader
npm install
php artisan sams:package
php artisan sams:export-sql
```

- `dist/sams-subir.zip` → sistema
- `storage/app/private/exports/*.sql` → base de datos (si hay que copiarla)

## En el hosting

1. Sube el ZIP y descomprímelo. **No borres ni reemplaces el `.env` que ya está en el servidor.**
2. Si la base es nueva, importa el `.sql` en phpMyAdmin.
3. Abre `https://tu-dominio/inicio`
4. Recarga con `Ctrl + F5`

Debe verse el logo pequeño, el carrusel y las tarjetas. El login usa los mismos usuarios de la base.

### Permisos

`storage/` y `bootstrap/cache/` deben ser escribibles.

### No subas

- `.env` de tu PC (rompe la conexión a MySQL del hosting)
- `public/hot` (rompe los estilos)
- `node_modules`
