# SAMS

Sistema de Administración y Seguimiento de Equipos (Prevention World).

## Subir a internet

En este PC:

```bash
composer install --no-dev --optimize-autoloader
php artisan sams:package
php artisan sams:export-sql
```

En el hosting: sube `dist/sams-subir.zip` **sin tocar el `.env` del servidor**, importa el SQL si la base es nueva y abre `/inicio`.

Guía completa: [docs/COMO-SUBIR.md](docs/COMO-SUBIR.md)

## Local (Laragon)

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
# No crear php artisan storage:link: fotos, firmas y documentos se sirven autenticados.
```

