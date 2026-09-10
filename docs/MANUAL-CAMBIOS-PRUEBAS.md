# Manual de cambios SAMS — ambiente de pruebas

Documento para revisar qué se modificó en esta etapa, dónde está cada cambio y cómo comprobarlo.

**Ambiente local:** `http://sams3.test/public`  
**Ambiente temporal:** `https://temporal.preventionworld.org`  
**Fecha:** 31 de agosto de 2026

---

## 1. Página de inicio (`/inicio`)

### Logo
- El logo de **Instituto Prevention World** queda fijo arriba al hacer scroll.
- Si esa imagen falla, se usa el logo de SAMS.
- Archivos: `public/images/logo-instituto.png` y `public/images/logo-principal.png`.

### Carrusel
Usa las fotos de `public/images`:
- Alturas
- Realidad virtual
- YEDY PW

Efectos: zoom suave, fundido entre diapositivas y cambio automático. Las versiones optimizadas son:

- `public/images/carousel-alturas.jpg`
- `public/images/carousel-realidad-virtual.jpg`
- `public/images/carousel-yedy-pw.jpg`

### Enlaces del inicio y del footer

| Elemento | Destino |
|---|---|
| Campus virtual | https://instituto.preventionworld.edu.co/ |
| Quiénes somos | se mantiene (portal institucionales) |
| Servicio al cliente | WhatsApp `317 712 6532` (`https://wa.me/573177126532`) |
| Soporte técnico | WhatsApp `324 980 4945` (`https://wa.me/573249804945`) |
| Trabaje con nosotros | sin enlace (muestra “Próximamente”) |
| Facebook | https://www.facebook.com/share/1G3b5MKFSF/ |
| Instagram | https://www.instagram.com/preventionworldqhsesas |
| TikTok | https://www.tiktok.com/@preventionworldqhsesas |
| WhatsApp (redes) | el mismo de servicio al cliente |

**Archivo principal:** `resources/views/dashboard.blade.php`

### Cómo probar
1. Abrir `/inicio`.
2. Verificar logo arriba y al hacer scroll.
3. Pasar las 3 fotos del carrusel.
4. Probar Campus virtual, WhatsApp, redes y que “Trabaje con nosotros” no abra nada.

---

## 2. Login

### Qué fallaba
A veces no dejaba entrar y, al volver atrás, sí. Causas típicas: token CSRF vencido, doble clic en el botón, o el formulario publicaba a otra URL.

### Qué se corrigió
- El formulario envía a la **misma URL** que se está viendo.
- Si la página expiró (419), vuelve al login con aviso y conserva el correo.
- El botón se bloquea al enviar (“INGRESANDO…”) para evitar doble envío.
- Después de entrar, manda al sitio correcto: panel, cambio de contraseña o completar perfil. Ya no redirige a `/home` (página que no existía).
- Mensajes en español.
- Máximo 5 intentos cada 15 minutos.
- La sesión se regenera al iniciar sesión (evita fijación de sesión).
- Al cerrar sesión se invalida por completo y vuelve a `/inicio`.

**Archivos:**
- `app/Http/Controllers/Auth/LoginController.php`
- `resources/views/auth/login.blade.php`
- `bootstrap/app.php`
- `app/Providers/AppServiceProvider.php`
- `lang/es/auth.php`, `lang/es/passwords.php`, `lang/es/validation.php`

### Cómo probar
1. Desde `/inicio` pulsar **Iniciar sesión**.
2. Probar un correo/clave incorrectos: debe decir *“Estas credenciales no coinciden con nuestros registros.”*
3. Entrar con un usuario real.
4. Si pide cambiar contraseña o completar perfil (foto, firma, documento), es normal.

---

## 3. Seguridad (ticket #004069)

Hallazgos de Zetta TIC. Se priorizaron **críticos** y **altos**.

### 3.1 XSS en HTML de formatos — CRÍTICO
El HTML de actas, inspecciones, hojas de vida, bajas y préstamos se limpia al mostrar.

- Directiva Blade nueva: `@safeHtml(...)`
- Clase: `app/Support/HtmlSanitizer.php`
- Vistas de `resources/views/admin/asignar/`, `inspeccion/`, `hoja_vida/`, `prestamos-temporales/`, `equipos/bajas/`, `exportar/`

**Probar:** en un formato editable no debe ejecutarse JavaScript pegado (`<script>` o `onerror=`).

### 3.2 Campos sensibles de usuario — CRÍTICO
Un usuario **no** puede enviarse por formulario: `password`, `role_id`, `empresa_id`, `active`, `must_change_password`.

Esos campos solo los asigna el sistema en procesos controlados (`User::createAccount` / `fillAccount`).

**Archivo:** `app/Models/User.php`  
**Controladores:** `UserController`, `RegisterController`, `EmpresaManagementController`

**Probar:** un usuario no puede cambiarse el rol ni la empresa manipulando la petición.

### 3.3 Permisos de rol — CRÍTICO
`permissions` ya no es asignable masivamente. Solo se guarda desde el módulo de roles, filtrando las claves permitidas.

**Archivo:** `app/Models/Role.php`, `app/Http/Controllers/RoleController.php`

### 3.4 Aislamiento entre empresas — ALTO
- La empresa activa en sesión se valida: no se acepta una empresa a la que el usuario no pertenezca.
- Si no hay empresa activa, no se da acceso automático a equipos.
- Archivos en `/storage/...`: se bloquea `../` y, si el archivo es de otra empresa (foto, firma, logo, imagen de equipo, PDF de inspección), se niega.

**Archivos:**
- `app/Services/EmpresaContext.php`
- `app/Policies/EquipoPolicy.php`
- `app/Policies/UserPolicy.php`
- `app/Http/Controllers/PublicStorageController.php`
- `app/Support/SafeStoragePath.php`

**Probar con dos empresas:**
1. Usuario A no debe ver equipos, usuarios ni archivos de la empresa B.
2. Cambiar la URL de un archivo de otra empresa no debe descargarlo.

### 3.5 Content Security Policy — ALTO
Se quitó `unsafe-eval` y se añadió `script-src-attr 'none'` (bloquea `onclick` maliciosos en HTML).

**Archivo:** `app/Http/Middleware/SecurityHeaders.php`

### 3.6 DomPDF / SSRF — ALTO
DomPDF ya no carga recursos remotos (`isRemoteEnabled = false`). Las imágenes del PDF salen del storage local.

**Archivos:** `InspeccionController.php`, `ExportarController.php`

### 3.7 Lectura de imágenes — ALTO
Toda lectura de archivo público pasa por `SafeStoragePath` (solo dentro de `storage/app/public`).

### 3.8 Consultas SQL de códigos — ALTO
`selectRaw` usa bindings. El tag del código solo admite letras y números.

**Archivo:** `app/Services/CodigoEquipoService.php`

### 3.9 Cambio de contraseña — ALTO
- En **perfil**: pide la contraseña actual.
- En **primera clave obligatoria**: no pide la anterior (el usuario no la eligió); sí regenera la sesión.

**Archivos:** `ProfileController.php`, `ForcedPasswordController.php`, vista `admin/profile/show.blade.php`

### 3.10 Cookies de sesión — ALTO
En `.env.example` quedó documentado:

- `SESSION_SECURE_COOKIE=true` en HTTPS de producción
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax`

**Importante en el servidor temporal/producción:** si el sitio es HTTPS, poner `SESSION_SECURE_COOKIE=true` en el `.env`.

### 3.11 Otros ajustes
| Punto | Cambio |
|---|---|
| Datos en modelos | `Role::permissions` y `AppSetting::value` ocultos al serializar |
| `modulos` / `code_settings` de empresa | fuera de asignación masiva |
| `innerHTML` | textos de usuario se escapan o van con `textContent` |
| `/inicio` | sigue pública (landing) |
| `/home` | exige login y manda al panel |
| Comando `sams:ensure-prevention-world` | pide confirmación, `--force`, bloqueado en producción sin `--force` |
| Número de documento | único en perfil y en alta/edición de usuarios |
| Verificación de correo | no se activó: las cuentas las crea un administrador |

---

## 4. Archivos nuevos

| Archivo | Para qué |
|---|---|
| `app/Support/SafeStoragePath.php` | Validar que un archivo esté dentro del storage público |
| `lang/es/auth.php` | Mensajes de login en español |
| `lang/es/passwords.php` | Mensajes de clave en español |
| `lang/es/validation.php` | Validaciones en español |
| `public/images/logo-instituto.png` | Logo institucional (copia web) |
| `public/images/logo-principal.png` | Logo SAMS (copia web) |
| `public/images/carousel-*.jpg` | Fotos del carrusel optimizadas |

---

## 5. Qué aún no se hizo (siguiente ronda)

- Policies propias para Role, Empresa, Grupo, Cargo, Sede y Bodega.
- SoftDeletes y reglas de borrado (cascade/restrict) en modelos críticos.
- Cifrado extra de datos personales en base de datos.
- CSP con *nonce* cuando se quite Tailwind CDN.
- Revisión del `.env` real del servidor (HTTPS, backups, logs, `APP_DEBUG=false`).

---

## 6. Lista rápida de pruebas antes de producción

- [ ] Logo y carrusel en `/inicio`
- [ ] Footer: campus, WhatsApp, redes, “Trabaje con nosotros” sin enlace
- [ ] Login correcto e incorrecto, en español
- [ ] Usuario de empresa A no ve datos de empresa B
- [ ] No se puede cambiar el propio rol ni `empresa_id`
- [ ] Pegar JavaScript en un formato editable no se ejecuta
- [ ] Cambio de contraseña en perfil pide la clave actual
- [ ] Un PDF/inspección no carga URLs internas del servidor
- [ ] Un archivo `/storage/...` de otra empresa no se descarga
- [ ] En HTTPS: `SESSION_SECURE_COOKIE=true` y `APP_DEBUG=false`

---

## 7. Dónde mirar el código

| Tema | Ruta |
|---|---|
| Inicio / footer / carrusel | `resources/views/dashboard.blade.php` |
| Login | `app/Http/Controllers/Auth/LoginController.php` |
| Sanitizar HTML | `app/Support/HtmlSanitizer.php` |
| Archivos seguros | `app/Support/SafeStoragePath.php` |
| Empresa activa | `app/Services/EmpresaContext.php` |
| Storage público | `app/Http/Controllers/PublicStorageController.php` |
| Cabeceras / CSP | `app/Http/Middleware/SecurityHeaders.php` |
| Usuario / campos sensibles | `app/Models/User.php` |
| Rol / permisos | `app/Models/Role.php` |
