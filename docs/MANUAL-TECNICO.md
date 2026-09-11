# Manual técnico de SAMS

**Sistema:** SAMS (Sistema de Administración y Seguimiento de Equipos)  
**Organización:** Prevention World / Instituto Prevention World  
**Versión:** 1.0.0  
**Fecha de revisión:** 2026-09-04  
**Clasificación:** Uso interno

## 1. Propósito y alcance

Este documento describe la instalación, configuración, arquitectura, operación y mantenimiento técnico de SAMS. Está dirigido a desarrolladores, administradores de servidores y responsables de soporte.

SAMS centraliza la administración multiempresa de equipos, ubicaciones, asignaciones, préstamos, inspecciones, bajas, documentos y exportaciones. La disponibilidad de cada módulo depende de los permisos del rol y de los módulos habilitados para la empresa activa.

## 2. Resumen de la solución

SAMS es una aplicación web monolítica construida sobre Laravel. El backend usa controladores, modelos Eloquent, policies, servicios y middleware. La interfaz usa vistas Blade, JavaScript, Vite, Bootstrap/Tailwind y Chart.js.

### 2.1 Tecnologías

| Componente | Versión o referencia |
| --- | --- |
| PHP | 8.2 o superior |
| Laravel | 12.x |
| Base de datos | SQLite para desarrollo; MySQL para pruebas y producción |
| Frontend | Vite 7 y Tailwind CSS 4 (se compilan en el PC; el servidor solo sirve `public/build`) |
| Documentos | Dompdf, FPDF, FPDI y PhpSpreadsheet/Maatwebsite Excel |
| Cola, sesiones y caché | En hosting: archivos (`file`) y cola `sync`. No hace falta Node ni worker. |
| Integraciones opcionales | Google Maps |

### 2.2 Directorios principales

- `app/`: backend, modelos, policies, servicios y middleware.
- `bootstrap/`: configuración de arranque.
- `config/`: configuración de aplicación e integraciones.
- `database/migrations/`: evolución del esquema.
- `public/`: punto de entrada y archivos compilados.
- `resources/views/`: vistas Blade y documentos.
- `resources/js/` y `resources/css/`: frontend.
- `routes/`: rutas web y de consola.
- `storage/`: logs, caché, sesiones y archivos cargados.
- `tests/`: pruebas unitarias y funcionales.
- `docs/`: documentación del proyecto.

## 3. Requisitos previos

- PHP 8.2+, Composer. Node.js y npm **solo en el PC de desarrollo** (para `npm run build`).
- Extensiones PHP `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `fileinfo`, `xml`, `ctype`, `json` y `tokenizer`.
- MySQL si se usa la configuración de pruebas o producción; SQLite está soportado para desarrollo.
- Servidor web con el document root apuntando a `public/` en producción (si apunta a la raíz del proyecto, el `.htaccess` raíz reenvía a `public/`).
- Permisos de escritura para `storage/` y `bootstrap/cache/`.

En Windows, Laragon es una opción válida para ejecutar el proyecto localmente.

## 4. Instalación local

Desde la raíz del proyecto:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan storage:link
```

El script `composer run setup` automatiza la instalación, la creación de `.env`, la clave de aplicación, las migraciones y la compilación frontend. Revisar `.env` antes de usarlo en un entorno compartido.

Para desarrollo con servidor, cola, logs y Vite:

```powershell
composer run dev
```

También pueden ejecutarse por separado: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail` y `npm run dev`.

## 5. Configuración de entorno

Las variables principales se encuentran en `.env.example`.

### 5.1 Aplicación y seguridad

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sams.example.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

No usar `APP_DEBUG=true`, `APP_URL=http://localhost` ni cookies no seguras en producción. Después de cambiar configuración:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.2 Base de datos

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3320
DB_DATABASE=sams3
DB_USERNAME=usuario_sams
DB_PASSWORD=una-clave-segura
```

Como sesiones, caché y colas usan base de datos por defecto, deben existir y estar migradas las tablas `sessions`, `cache`, `jobs`, `job_batches` y `failed_jobs`, además de las tablas funcionales.

### 5.3 Archivos e integraciones

- Disco `local`: `storage/app/private`, para archivos privados.
- Disco `public`: `storage/app/public`, para imágenes y archivos gestionados por la aplicación.
- Enlace requerido: `public/storage` hacia `storage/app/public`.
- No exponer directamente `storage/app/private` mediante el servidor web.

```env
GOOGLE_MAPS_API_KEY=
SAMS_ALLOW_REGISTRATION=false
```

La geocodificación necesita `GOOGLE_MAPS_API_KEY`. Las claves son secretos y no deben almacenarse en el repositorio.

## 6. Arquitectura y seguridad

### 6.1 Flujo de una petición

1. El servidor web entrega `public/index.php` a Laravel.
2. `bootstrap/app.php` registra rutas, middleware y manejo de excepciones.
3. Laravel valida sesión, CSRF y cabeceras de seguridad.
4. Las rutas autenticadas pasan por `auth` y, según el caso, por `password.must_change` y `profile.complete`.
5. El controlador valida la entrada, aplica policies/permisos y devuelve una vista, JSON o documento.

### 6.2 Autenticación y autorización

- Guard `web` con sesiones y proveedor Eloquent basado en `App\Models\User`.
- `must_change_password` obliga a definir una contraseña segura.
- Un perfil incompleto debe completar foto, firma, tipo y número de documento.
- El aislamiento multiempresa usa `empresa_id`, empresa activa en sesión, permisos por módulo y policies.
- El administrador global es un usuario sin `empresa_id`; solo este perfil puede descargar el manual desde la aplicación.
- La geocodificación tiene limitación de frecuencia.

Middleware relevante: `auth`, `password.must_change`, `profile.complete`, `SecurityHeaders` y `PreventAuthPageCache`.

## 7. Módulos funcionales

### 7.1 Administración y organización

Usuarios, roles, cargos, grupos y fabricantes. Los roles almacenan permisos por módulo y los usuarios se relacionan con empresa, cargo y grupo.

### 7.2 Gestión multiempresa

Empresas, sedes, bodegas, oficinas y espacios. El contexto activo se resuelve mediante `EmpresaContext` y la autorización mediante `EmpresaModuleAuthorization`.

### 7.3 Inventario

Tipos y clases de equipo, códigos reutilizables, equipos, ubicaciones, estados, imágenes, archivos adjuntos y kits. Controladores principales: `EquipoInventarioController` y `EquipoManagementController`.

### 7.4 Ciclo de vida del equipo

Asignaciones, solicitudes con aceptación firmada, seguimiento, devoluciones, préstamos temporales, bajas, auditoría, traspasos y material didáctico.

### 7.5 Hoja de vida e inspecciones

Plantillas Excel, datos HTML, evidencias, firmas, inspecciones y documentos PDF. Los flujos principales están en `HojaVidaController`, `InspeccionController` y `FormatosController`.

### 7.6 Exportaciones y documentos

Se generan hojas de vida, inspecciones, bajas y hojas de vida completas. Dompdf genera PDF desde HTML; FPDF/FPDI combina documentos; PhpSpreadsheet/Maatwebsite Excel interviene en archivos Excel.

### 7.7 Utilidades

Existen sugerencias y geocodificación de empresas.

## 8. Base de datos y migraciones

Las migraciones son la fuente de evolución del esquema:

```powershell
php artisan migrate
php artisan migrate:status
```

Antes de actualizar una instalación existente: respaldar la base de datos y `storage/app`, revisar migraciones pendientes, ejecutar `php artisan migrate --force` en una ventana controlada y verificar login, empresa activa, inventario, archivos y documentos.

El historial contiene migraciones evolutivas relacionadas con nombres de tablas de tipos/clases de equipos. Probar por separado una instalación desde base vacía y una actualización desde una base antigua.

## 9. Operación y mantenimiento

```powershell
php artisan about
php artisan route:list
php artisan queue:failed
php artisan queue:retry all
php artisan storage:link
php artisan optimize:clear
php artisan test
```

Comandos propios detectados:

- `php artisan sams:package`: genera `dist/sams-subir.zip` listo para el hosting.
- `php artisan sams:export-sql`: exporta la base a un `.sql` para phpMyAdmin.
- `php artisan hoja-vida:pdf {clase} {equipo} {--force=0}`: genera o regenera el PDF de una hoja de vida.
- `php artisan passwords:update-bcrypt`: mantenimiento de contraseñas.
- `php artisan sams:ensure-prevention-world`: normaliza datos de Prevention World.
- `php artisan sams:prune-demo-data`: elimina datos de demostración.

Los dos últimos comandos pueden ser destructivos. Ejecutarlos solo con respaldo, autorización y revisión de su implementación.

### 9.1 Logs y colas

Revisar `storage/logs/laravel.log` y los errores del servidor web/PHP. Con `QUEUE_CONNECTION=database`, mantener un worker activo:

```powershell
php artisan queue:work --tries=3 --timeout=120
```

Supervisar `failed_jobs` y corregir la causa antes de reintentar trabajos masivamente.

## 10. Pruebas

```powershell
composer test
```

o `php artisan test`.

La configuración de PHPUnit usa MySQL en `localhost:3320`, base `sams3`, usuario `root` y contraseña vacía. Confirmar que ese servicio exista antes de ejecutar la suite. La cobertura actual incluye pruebas básicas de rutas y ejemplos; debe ampliarse para login, permisos multiempresa, inventario, asignaciones, archivos, PDF/Excel, comandos y proveedores externos.

## 11. Despliegue de producción

Flujo corto (recomendado): ver [COMO-SUBIR.md](COMO-SUBIR.md).

En el PC:

```powershell
composer install --no-dev --optimize-autoloader
npm run build
php artisan sams:package
php artisan sams:export-sql
```

En el servidor:

1. Subir `dist/sams-subir.zip` y descomprimirlo **sin reemplazar el `.env` del servidor**.
2. Importar el SQL en phpMyAdmin **solo si la base es nueva**.
3. Abrir `/inicio` y recargar con Ctrl+F5.
4. Comprobar `/inicio` (estilos Tailwind, no el CSS viejo de Bootstrap).
5. Permisos de escritura en `storage/` y `bootstrap/cache/`.

El document root ideal es `public/`. Si el hosting usa la raíz del proyecto, el `.htaccess` raíz reenvía a `public/`.

No hace falta Node, `npm run build` ni un worker de colas en el hosting: las sesiones y la caché van a archivos y la cola corre en `sync`.

## 12. Respaldo y recuperación

Respaldar conjuntamente la base de datos, `storage/app`, `.env` de forma cifrada y la configuración del servidor/worker. Probar periódicamente la restauración en un entorno separado; un respaldo no verificado no es un procedimiento de recuperación confiable.

## 13. Riesgos y pendientes técnicos

- Validar accesos cruzados entre empresas para todos los recursos identificados por ID.
- Revisar que cada tipo de archivo descargable aplique control de pertenencia empresarial.
- Mantener `APP_DEBUG=false`, HTTPS y `SESSION_SECURE_COOKIE=true` en producción.
- Definir un proveedor de correo real si se requieren notificaciones.
- Confirmar `upload_max_filesize`, `post_max_size`, `memory_limit` y `max_execution_time` para PDF/Excel grandes.
- Ampliar pruebas automatizadas de seguridad y flujos críticos.
- Documentar la rotación de claves de IA y Google Maps.

## 14. Referencias del código

- [README.md](../README.md)
- [composer.json](../composer.json)
- [package.json](../package.json)
- [routes/web.php](../routes/web.php)
- [bootstrap/app.php](../bootstrap/app.php)
- [config/filesystems.php](../config/filesystems.php)
- [config/queue.php](../config/queue.php)
- [app/Http/Controllers/ManualTecnicoController.php](../app/Http/Controllers/ManualTecnicoController.php)
- [docs/MANUAL-CAMBIOS-PRUEBAS.md](MANUAL-CAMBIOS-PRUEBAS.md)