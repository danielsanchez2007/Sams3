# Cómo subir SAMS (sistema + base de datos)

El servidor **no necesita Node.js**. Los estilos se compilán en el PC y viajan con el código.

## Cómo queda conectado

```
Navegador
   │
   ├─ /inicio  /login  /admin  →  public/index.php  →  Laravel
   │
   ├─ /build/assets/*.css|js   →  estilos Tailwind (Vite)
   ├─ /css/sams.css /js/sams.js →  copia estable por si falta el hash
   │
   └─ MySQL  ←  .env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
                 Sesiones y caché van en storage/ (archivos), no en la cola
```

1. **Archivos PHP** (`app/`, `resources/views/`, `routes/`) pintan pantallas y guardan datos.
2. **`public/build`** y **`public/css/sams.css`** son el diseño. Sin esa carpeta el logo se ve enorme.
3. **MySQL** guarda usuarios, equipos, empresas. El `.env` es el cable entre PHP y la base.
4. **`storage/app/public`** guarda fotos y firmas. Si el hosting no deja crear el enlace `public/storage`, SAMS sirve esos archivos por la ruta `/storage/...`.
5. **`/instalar`** escribe el `.env`, prueba MySQL y deja el sitio listo.

## En tu PC (una sola vez por versión)

```bash
composer install --no-dev --optimize-autoloader
npm install
php artisan sams:package
```

Eso genera `dist/sams-subir.zip` con `vendor` y los estilos.

Para sacar la base actual:

```bash
php artisan sams:export-sql
```

El `.sql` queda en `storage/app/private/exports/`.

## En el hosting

1. Sube y descomprime el ZIP en la carpeta del dominio.
2. En phpMyAdmin: crea la base **o** importa el `.sql`.
3. Entra a `https://tu-dominio/instalar`.
4. Pon la URL del sitio y los datos MySQL.
5. Elige:
   - **Ya importé el SQL** si subiste usuarios y equipos.
   - **Crear tablas nuevas** si la base está vacía (pide un administrador).
6. Abre `/inicio`. Debe verse el carrusel y el logo pequeño.

### Permisos

`storage/` y `bootstrap/cache/` deben ser escribibles.

### No subas

- `.env` de tu PC (el instalador crea uno en el servidor)
- `public/hot` (rompe estilos: apunta a Vite de localhost)
- `node_modules`

Si el dominio apunta a la raíz del proyecto (no a `public/`), el `.htaccess` de la raíz reenvía el tráfico a `public/`.
