# SAMS

Sistema de Administración y Seguimiento de Equipos (Prevention World).

## Subir a internet

En este PC:

```bash
composer install --no-dev --optimize-autoloader
php artisan sams:package
php artisan sams:export-sql
```

En el hosting: sube `dist/sams-subir.zip`, importa el SQL (o deja la base vacía) y abre `/instalar`.

Guía completa: [docs/COMO-SUBIR.md](docs/COMO-SUBIR.md)

## Local (Laragon)

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

